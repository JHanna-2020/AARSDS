function toggleDarkMode() {
  const body = document.body;
  const btn = document.getElementById('themeToggle');

  body.classList.toggle('dark-mode');

  if (body.classList.contains('dark-mode')) {
    localStorage.setItem('theme', 'dark');
  } else {
    localStorage.setItem('theme', 'light');
  }
}

window.addEventListener('DOMContentLoaded', () => {
  const theme = localStorage.getItem('theme');
  const btn = document.getElementById('themeToggle');
});