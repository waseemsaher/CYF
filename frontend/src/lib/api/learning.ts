import { apiGet, apiPost } from './client';

export type Translation = {
  ar: string;
  en: string;
};

export type CourseItem = {
  id: number;
  type: 'lecture_link' | 'external_link' | 'file' | 'quiz' | 'exam' | 'text';
  title: Translation;
  description?: Translation | null;
  url?: string | null;
  has_file?: boolean;
  telegram_message_id?: number | null;
  has_telegram_video?: boolean;
  is_locked: boolean;
  position: number;
  quiz?: {
    id: number;
    kind: 'quiz' | 'exam';
    title: Translation;
    duration_minutes: number | null;
    max_attempts: number;
    available_from: string | null;
    available_until: string | null;
  } | null;
};

export type CourseSection = {
  id: number;
  title: Translation;
  position: number;
  items: CourseItem[];
};

export type CourseContentResponse = {
  course: {
    id: number;
    slug: string;
    title: Translation;
    telegram_invite_link: string | null;
  };
  is_unlocked: boolean;
  sections: CourseSection[];
};

export type QuizQuestionOption = {
  id: number;
  text: Translation;
  is_correct?: boolean;
};

export type QuizQuestion = {
  id: number;
  type: 'mcq' | 'true_false';
  text: Translation;
  points: number;
  position: number;
  options: QuizQuestionOption[];
};

export type StartQuizResponse = {
  attempt: {
    id: number;
    status: string;
    started_at: string;
    duration_minutes: number | null;
  };
  quiz: {
    id: number;
    title: Translation;
    kind: 'quiz' | 'exam';
  };
  questions: QuizQuestion[];
};

export type QuizResultBreakdown = {
  question_id: number;
  text: Translation;
  explanation?: Translation | null;
  points: number;
  points_awarded: number;
  is_correct: boolean;
  selected_option_ids: number[];
  options: QuizQuestionOption[];
};

export type QuizResultResponse = {
  id: number;
  quiz_id: number;
  status: string;
  started_at: string;
  submitted_at: string | null;
  visibility: 'immediate' | 'after_close' | 'hidden';
  score: number | null;
  max_score: number | null;
  percentage: number | null;
  breakdown?: QuizResultBreakdown[];
};

export function getCourseContent(fetcher: typeof fetch = fetch, slug: string) {
  return apiGet<{ data: CourseContentResponse }>(fetcher, `/courses/${slug}/content`);
}

export function startQuiz(fetcher: typeof fetch = fetch, quizId: number) {
  return apiPost<{ data: StartQuizResponse }>(fetcher, `/quizzes/${quizId}/start`, {});
}

export function submitQuiz(
  fetcher: typeof fetch = fetch,
  quizId: number,
  attemptId: number,
  answers: Record<number, number[]>
) {
  return apiPost<{ data: QuizResultResponse }>(
    fetcher,
    `/quizzes/${quizId}/attempts/${attemptId}/submit`,
    { answers }
  );
}

export function getQuizResult(
  fetcher: typeof fetch = fetch,
  quizId: number,
  attemptId: number
) {
  return apiGet<{ data: QuizResultResponse }>(
    fetcher,
    `/quizzes/${quizId}/attempts/${attemptId}`
  );
}
