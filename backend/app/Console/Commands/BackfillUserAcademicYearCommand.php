<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Console\Command;

class BackfillUserAcademicYearCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'users:backfill-academic-year';

    /**
     * @var string
     */
    protected $description = 'Match each user\'s academic_year text against academic_years.name->ar and backfill academic_year_id';

    public function handle(): int
    {
        $years = AcademicYear::all();
        $yearMap = [];

        foreach ($years as $year) {
            $arName = $year->getTranslation('name', 'ar');
            if (is_string($arName) && $arName !== '') {
                $yearMap[trim($arName)] = $year->getKey();
            }
        }

        $users = User::all();
        $autoLinked = 0;
        $unmatched = [];
        $alreadyLinked = 0;
        $noYearSet = 0;

        foreach ($users as $user) {
            if ($user->getAttribute('academic_year_id') !== null) {
                $alreadyLinked++;

                continue;
            }

            $academicYearStr = $user->getAttribute('academic_year');

            if ($academicYearStr === null || trim((string) $academicYearStr) === '') {
                $noYearSet++;
                $unmatched[] = [
                    'id' => $user->getKey(),
                    'name' => $user->getAttribute('name'),
                    'email' => $user->getAttribute('email'),
                    'academic_year' => 'NULL / Empty',
                    'reason' => 'No academic year string set on user record',
                ];

                continue;
            }

            $trimmedStr = trim((string) $academicYearStr);

            if (isset($yearMap[$trimmedStr])) {
                $user->setAttribute('academic_year_id', $yearMap[$trimmedStr]);
                $user->save();
                $autoLinked++;
                $this->info("Auto-linked User ID {$user->getKey()} ({$user->getAttribute('email')}) to Academic Year ID {$yearMap[$trimmedStr]} ('{$trimmedStr}')");
            } else {
                $unmatched[] = [
                    'id' => $user->getKey(),
                    'name' => $user->getAttribute('name'),
                    'email' => $user->getAttribute('email'),
                    'academic_year' => $trimmedStr,
                    'reason' => 'Exact match not found in academic_years table',
                ];
            }
        }

        $this->info("Backfill summary: {$autoLinked} auto-linked, {$alreadyLinked} already linked, {$noYearSet} with no academic year set, ".count($unmatched).' total requiring review/unmatched.');

        if (! empty($unmatched)) {
            $this->warn('Users needing manual review or with no academic year set:');
            foreach ($unmatched as $u) {
                $this->line("- ID: {$u['id']} | Name: {$u['name']} | Email: {$u['email']} | academic_year: '{$u['academic_year']}' | Reason: {$u['reason']}");
            }
        }

        return self::SUCCESS;
    }
}
