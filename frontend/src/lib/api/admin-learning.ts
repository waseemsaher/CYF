import { apiGet, apiPost, apiPut, apiDelete } from './client';

export type MultilingualText = {
  ar: string;
  en: string;
};

export type CourseItemType = 'lecture_link' | 'external_link' | 'file' | 'quiz' | 'exam' | 'text';

export type CourseQuiz = {
  id: number;
  course_id?: number;
  kind: 'quiz' | 'exam';
  title: MultilingualText;
  duration_minutes: number | null;
  max_attempts: number;
  available_from: string | null;
  available_until: string | null;
  shuffle_questions?: boolean;
  shuffle_options?: boolean;
  results_visibility?: 'immediate' | 'after_close' | 'hidden';
};

export type QuestionOption = {
  id?: number;
  text: MultilingualText;
  is_correct: boolean;
};

export type QuizQuestion = {
  id: number;
  quiz_id: number;
  type: 'mcq' | 'true_false';
  text: MultilingualText;
  explanation?: MultilingualText | null;
  points: number;
  position: number;
  options: QuestionOption[];
};

export type CourseItem = {
  id: number;
  course_id?: number;
  section_id?: number;
  type: CourseItemType;
  title: MultilingualText;
  description?: MultilingualText | null;
  url?: string | null;
  has_file?: boolean;
  file_path?: string | null;
  telegram_message_id?: number | null;
  has_telegram_video?: boolean;
  position: number;
  is_published?: boolean;
  is_locked?: boolean;
  quiz?: CourseQuiz | null;
};

export type CourseSection = {
  id: number;
  course_id?: number;
  title: MultilingualText;
  position: number;
  items: CourseItem[];
};

export type CourseContentResponse = {
  data: {
    course: {
      id: number;
      slug: string;
      title: MultilingualText;
      telegram_invite_link?: string | null;
    };
    is_unlocked: boolean;
    sections: CourseSection[];
  };
};

/**
 * Fetch course content outline including sections, items, and quizzes.
 * Endpoint lives in CourseContentController (GET /courses/{slug}/content).
 */
export function getCourseContent(fetcher: typeof fetch = fetch, slug: string) {
  return apiGet<CourseContentResponse>(fetcher, `/courses/${slug}/content`);
}

// ================= Sections =================

export type CreateSectionPayload = {
  title: MultilingualText;
  position?: number | null;
};

export type UpdateSectionPayload = {
  title?: MultilingualText;
  position?: number | null;
};

export function createCourseSection(
  fetcher: typeof fetch = fetch,
  courseId: number,
  payload: CreateSectionPayload
) {
  return apiPost<{ data: CourseSection }>(fetcher, `/admin/courses/${courseId}/sections`, payload);
}

export function updateCourseSection(
  fetcher: typeof fetch = fetch,
  sectionId: number,
  payload: UpdateSectionPayload
) {
  return apiPut<{ data: CourseSection }>(fetcher, `/admin/sections/${sectionId}`, payload);
}

export function deleteCourseSection(fetcher: typeof fetch = fetch, sectionId: number) {
  return apiDelete<{ message: string }>(fetcher, `/admin/sections/${sectionId}`);
}

// ================= Items =================

export type CreateItemPayload = {
  type: CourseItemType;
  title: MultilingualText;
  description?: MultilingualText | null;
  url?: string | null;
  file?: File | null;
  quiz_id?: number | null;
  position?: number | null;
  is_published?: boolean;
};

export type UpdateItemPayload = {
  title?: MultilingualText;
  description?: MultilingualText | null;
  url?: string | null;
  telegram_message_id?: number | null;
  position?: number | null;
  is_published?: boolean;
};

export function createCourseItem(
  fetcher: typeof fetch = fetch,
  courseId: number,
  sectionId: number,
  payload: CreateItemPayload
) {
  if (payload.file) {
    const formData = new FormData();
    formData.append('type', payload.type);
    formData.append('title[ar]', payload.title.ar);
    formData.append('title[en]', payload.title.en);
    if (payload.description?.ar) formData.append('description[ar]', payload.description.ar);
    if (payload.description?.en) formData.append('description[en]', payload.description.en);
    if (payload.url) formData.append('url', payload.url);
    if (payload.quiz_id) formData.append('quiz_id', String(payload.quiz_id));
    if (payload.position !== undefined && payload.position !== null) {
      formData.append('position', String(payload.position));
    }
    formData.append('is_published', payload.is_published !== false ? '1' : '0');
    formData.append('file', payload.file);

    return apiPost<{ data: CourseItem }>(
      fetcher,
      `/admin/courses/${courseId}/sections/${sectionId}/items`,
      formData
    );
  }

  return apiPost<{ data: CourseItem }>(
    fetcher,
    `/admin/courses/${courseId}/sections/${sectionId}/items`,
    payload
  );
}

export function updateCourseItem(
  fetcher: typeof fetch = fetch,
  itemId: number,
  payload: UpdateItemPayload
) {
  return apiPut<{ data: CourseItem }>(fetcher, `/admin/items/${itemId}`, payload);
}

export function deleteCourseItem(fetcher: typeof fetch = fetch, itemId: number) {
  return apiDelete<{ message: string }>(fetcher, `/admin/items/${itemId}`);
}

// ================= Quizzes & Questions =================

export type CreateQuizPayload = {
  kind: 'quiz' | 'exam';
  title: MultilingualText;
  duration_minutes?: number | null;
  max_attempts?: number | null;
  available_from?: string | null;
  available_until?: string | null;
  shuffle_questions?: boolean;
  shuffle_options?: boolean;
  results_visibility?: 'immediate' | 'after_close' | 'hidden';
};

export function createCourseQuiz(
  fetcher: typeof fetch = fetch,
  courseId: number,
  payload: CreateQuizPayload
) {
  return apiPost<{ data: CourseQuiz }>(fetcher, `/admin/courses/${courseId}/quizzes`, payload);
}

export type CreateQuestionPayload = {
  type: 'mcq' | 'true_false';
  text: MultilingualText;
  explanation?: MultilingualText | null;
  points?: number | null;
  options: {
    text: MultilingualText;
    is_correct: boolean;
  }[];
};

export function createQuizQuestion(
  fetcher: typeof fetch = fetch,
  quizId: number,
  payload: CreateQuestionPayload
) {
  return apiPost<{ data: QuizQuestion }>(fetcher, `/admin/quizzes/${quizId}/questions`, payload);
}

export function deleteQuizQuestion(fetcher: typeof fetch = fetch, questionId: number) {
  return apiDelete<{ message: string }>(fetcher, `/admin/questions/${questionId}`);
}
