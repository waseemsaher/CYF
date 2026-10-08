import puppeteer from 'puppeteer-core';
import { execSync } from 'child_process';

const BASE_URL = 'http://127.0.0.1:4173';

async function run() {
  console.log('=== Starting Full E2E Puppeteer Verification for Teacher & Content Editor ===');

  const browser = await puppeteer.launch({
    executablePath: '/usr/bin/chromium',
    headless: 'new',
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-gpu',
    ],
  });

  const page = await browser.newPage();
  page.setViewport({ width: 1366, height: 900 });

  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      console.log(`[Browser Console Error] ${msg.text()}`);
    }
  });

  try {
    // 1. Login as Admin
    console.log('Step 1: Logging in as superadmin...');
    await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle0' });

    await page.waitForSelector('input[type="email"]');
    await page.type('input[type="email"]', 'admin@example.com');
    await page.type('input[type="password"]', 'password');

    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle0' }).catch(() => {}),
      page.click('button[type="submit"]'),
    ]);

    await new Promise((r) => setTimeout(r, 1000));
    console.log('Logged in successfully, current URL:', page.url());

    // 2. Go to Admin Dashboard & Teachers Tab
    console.log('Step 2: Navigating to /admin and opening Teachers tab...');
    await page.goto(`${BASE_URL}/admin`, { waitUntil: 'networkidle0' });
    await page.waitForSelector('.admin-tabs', { timeout: 10000 });

    await page.waitForSelector('[data-testid="tab-teachers"]');
    await page.click('[data-testid="tab-teachers"]');
    await page.waitForSelector('[data-testid="btn-add-teacher"]');

    // 3. Create New Teacher
    const uniqueEmail = `teacher.e2e.${Date.now()}@example.com`;
    const teacherName = 'د. مصطفى الشريف E2E';
    console.log(`Step 3: Creating teacher "${teacherName}" with email "${uniqueEmail}"...`);

    await page.click('[data-testid="btn-add-teacher"]');
    await page.waitForSelector('[data-testid="input-teacher-name"]');

    await page.type('[data-testid="input-teacher-name"]', teacherName);
    await page.type('[data-testid="input-teacher-email"]', uniqueEmail);
    await page.type('[data-testid="input-teacher-password"]', 'Secret1234!');
    await page.type('[data-testid="input-teacher-phone"]', '01012345678');

    await page.click('[data-testid="btn-submit-teacher"]');

    // Wait for the modal to close and row to appear in table
    await page.waitForFunction(
      (email) => document.body.innerText.includes(email),
      { timeout: 10000 },
      uniqueEmail
    );
    console.log('Teacher successfully created and rendered in UI!');

    // Get created teacher ID from database
    const createdTeacherId = execSync(
      `sqlite3 backend/database/database.sqlite "SELECT id FROM users WHERE email='${uniqueEmail}';"`
    ).toString().trim();
    console.log(`Created Teacher ID in database: ${createdTeacherId}`);

    // 4. Assign Teacher to Course 2 via Course Modal in Courses Tab
    console.log('Step 4: Switching to Courses tab to assign teacher via Course Modal picker...');
    const coursesTabBtn = await page.waitForSelector('button.tab-btn');
    await coursesTabBtn.click();
    await new Promise((r) => setTimeout(r, 800));

    // Click edit on Course 2
    const editBtns = await page.$$('button.btn-edit');
    if (editBtns.length >= 2) {
      await editBtns[1].click();
    } else {
      await editBtns[0].click();
    }

    await page.waitForSelector('[data-testid="select-course-teacher"]');
    await page.waitForFunction(
      (tid) => !!document.querySelector(`[data-testid="select-course-teacher"] option[value="${tid}"]`),
      { timeout: 5000 },
      createdTeacherId
    );
    await page.select('[data-testid="select-course-teacher"]', createdTeacherId);

    // Set share to 80%
    await page.click('[data-testid="input-course-teacher-share"]', { clickCount: 3 });
    await page.type('[data-testid="input-course-teacher-share"]', '80');

    // Submit course form
    await page.click('form.modal-form button[type="submit"]');
    await new Promise((r) => setTimeout(r, 1500));
    console.log('Teacher assigned to course with 80% share via course modal!');

    // 5. Navigate to Course Content Editor for Course 1
    console.log('Step 5: Navigating to /admin/courses/1/content...');
    await page.goto(`${BASE_URL}/admin/courses/1/content`, { waitUntil: 'networkidle0' });
    await page.waitForSelector('[data-testid="btn-add-section"]', { timeout: 10000 });

    // 6. Create Section
    const sectionTitleAr = `فصل الخوارزميات E2E ${Date.now()}`;
    const sectionTitleEn = 'Algorithms Section E2E';
    console.log(`Step 6: Creating section "${sectionTitleAr}"...`);

    await page.click('[data-testid="btn-add-section"]');
    await page.waitForSelector('[data-testid="input-section-title-ar"]');

    await page.type('[data-testid="input-section-title-ar"]', sectionTitleAr);
    await page.type('[data-testid="input-section-title-en"]', sectionTitleEn);
    await page.click('[data-testid="btn-submit-section"]');

    await page.waitForFunction(
      (title) => document.body.innerText.includes(title),
      { timeout: 10000 },
      sectionTitleAr
    );
    console.log('Section successfully created and rendered in UI!');

    // 7. Add Item to Section with Telegram Message ID
    console.log('Step 7: Adding an item with telegram_message_id inside the section...');
    const addButtons = await page.$$('button[data-testid^="btn-add-item-"]');
    const addItemBtn = addButtons[addButtons.length - 1];
    await addItemBtn.click();

    await page.waitForSelector('[data-testid="input-item-title-ar"]');
    const itemTitleAr = `محاضرة الشرح بالفيديو E2E ${Date.now()}`;
    await page.type('[data-testid="input-item-title-ar"]', itemTitleAr);
    await page.type('[data-testid="input-item-title-en"]', 'Video Lecture E2E');
    await page.type('[data-testid="input-item-url"]', 'https://youtu.be/test_video_123');
    await page.type('[data-testid="input-item-telegram-id"]', '56789');

    await page.click('[data-testid="btn-submit-item"]');
    await new Promise((r) => setTimeout(r, 1500));

    await page.waitForFunction(
      (title) => document.body.innerText.includes(title),
      { timeout: 10000 },
      itemTitleAr
    );
    console.log('Course item with telegram_message_id #56789 successfully created in UI!');

    // 8. Create Quiz and Add Question
    console.log('Step 8: Creating quiz and adding a question...');
    await page.click('[data-testid="btn-add-quiz"]');
    await page.waitForSelector('[data-testid="input-quiz-title-ar"]');

    const quizTitleAr = `كويز الخوارزميات E2E ${Date.now()}`;
    await page.type('[data-testid="input-quiz-title-ar"]', quizTitleAr);
    await page.type('[data-testid="input-quiz-title-en"]', 'Algorithms E2E Quiz');
    await page.click('[data-testid="btn-submit-quiz"]');

    // Wait for questions builder modal
    await page.waitForSelector('[data-testid="input-question-text-ar"]');
    const questionTextAr = `ما هو أفضل تعقيد زمني للبحث في شجرة بحث ثنائية متوازنة؟ ${Date.now()}`;
    await page.type('[data-testid="input-question-text-ar"]', questionTextAr);
    await page.type('[data-testid="input-question-text-en"]', 'Best search time complexity?');

    await page.type('[data-testid="input-option-ar-0"]', 'O(log n)');
    await page.type('[data-testid="input-option-ar-1"]', 'O(n)');

    await page.click('[data-testid="btn-submit-question"]');
    await new Promise((r) => setTimeout(r, 1500));

    // Close question modal
    const closeBtns = await page.$$('button.btn-cancel');
    for (const btn of closeBtns) {
      const text = await page.evaluate((el) => el.innerText, btn);
      if (text.includes('إنهاء وإغلاق')) {
        await btn.click();
        break;
      }
    }
    console.log('Quiz and Question successfully created in UI!');

    // 9. Capture proof screenshot
    const screenshotPath = '/home/kaminari0x/cyf/admin-ui-proof.png';
    await page.screenshot({ path: screenshotPath, fullPage: true });
    console.log(`Proof screenshot saved to ${screenshotPath}`);

    // 10. Query SQLite Database directly to verify persistence of all entities!
    console.log('\n=== DIRECT DATABASE VERIFICATION ===');

    const dbTeacher = execSync(
      `sqlite3 backend/database/database.sqlite "SELECT id, name, email, created_at FROM users WHERE email='${uniqueEmail}';"`
    ).toString().trim();
    console.log('1. Database Teacher Record:', dbTeacher);

    const dbCourseTeacher = execSync(
      `sqlite3 backend/database/database.sqlite "SELECT course_id, teacher_id, teacher_share_percent FROM course_teacher WHERE teacher_id=${createdTeacherId};"`
    ).toString().trim();
    console.log('2. Database Course-Teacher Assignment Record:', dbCourseTeacher);

    const dbSection = execSync(
      `sqlite3 backend/database/database.sqlite "SELECT id, course_id, title, position FROM course_sections WHERE title LIKE '%${sectionTitleAr}%';"`
    ).toString().trim();
    console.log('3. Database Section Record:', dbSection);

    const dbItem = execSync(
      `sqlite3 backend/database/database.sqlite "SELECT id, course_id, section_id, type, telegram_message_id, position FROM course_items WHERE telegram_message_id=56789 ORDER BY id DESC LIMIT 1;"`
    ).toString().trim();
    console.log('4. Database Course Item Record (with telegram_message_id):', dbItem);

    const dbQuiz = execSync(
      `sqlite3 backend/database/database.sqlite "SELECT id, course_id, kind, title FROM quizzes WHERE title LIKE '%${quizTitleAr}%';"`
    ).toString().trim();
    console.log('5. Database Quiz Record:', dbQuiz);

    const dbQuestion = execSync(
      `sqlite3 backend/database/database.sqlite "SELECT id, quiz_id, type, points, text FROM questions WHERE text LIKE '%${questionTextAr}%';"`
    ).toString().trim();
    console.log('6. Database Question Record:', dbQuestion);

    if (dbTeacher && dbCourseTeacher && dbSection && dbItem && dbQuiz && dbQuestion) {
      console.log('\n>>> SUCCESS: ALL 6 ENTITIES FULLY CREATED AND VERIFIED DIRECTLY IN SQLITE DATABASE! <<<');
    } else {
      throw new Error('Database verification failed: some records are missing.');
    }
  } finally {
    await browser.close();
  }
}

run().catch((err) => {
  console.error('E2E Test Failed:', err);
  process.exit(1);
});
