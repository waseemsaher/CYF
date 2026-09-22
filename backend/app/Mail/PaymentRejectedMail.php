<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Payment $payment,
    ) {}

    public function envelope(): Envelope
    {
        $locale = $this->payment->user->getAttribute('locale') ?? 'ar';
        $subject = $locale === 'en'
            ? 'Payment Rejected — ' . ($this->payment->course->getTranslation('title', 'en'))
            : 'تم رفض الدفع — ' . ($this->payment->course->getTranslation('title', 'ar'));

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payment-rejected',
            with: [
                'payment' => $this->payment,
                'course' => $this->payment->course,
                'user' => $this->payment->user,
                'reason' => $this->payment->getAttribute('rejection_reason'),
                'locale' => $this->payment->user->getAttribute('locale') ?? 'ar',
            ],
        );
    }
}
