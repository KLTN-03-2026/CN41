import fs from 'fs';
import path from 'path';

const pages = [
  'src/pages/auth/AdminLoginPage.vue',
  'src/pages/admin/DashboardPage.vue',
  'src/pages/admin/CoursesPage.vue',
  'src/pages/admin/CourseFormPage.vue',
  'src/pages/admin/CategoriesPage.vue',
];

pages.forEach(file => {
  const fullPath = path.resolve(file);
  const dir = path.dirname(fullPath);
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
  if (!fs.existsSync(fullPath)) {
    const componentName = path.basename(file, '.vue');
    const content = `<template>\n  <div class="p-6">\n    <h1 class="text-2xl font-bold">${componentName}</h1>\n  </div>\n</template>\n\n<script>\nexport default {\n  name: '${componentName}'\n}\n</script>\n`;
    fs.writeFileSync(fullPath, content);
  }
});

console.log('Created placeholder pages.');
