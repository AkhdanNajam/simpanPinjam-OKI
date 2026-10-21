var toggleBtn = document.getElementById('nav-toggle-btn');
var nav = document.querySelector('header nav');
if (toggleBtn && nav) {
  toggleBtn.addEventListener('click', function () { nav.classList.toggle('open'); });
}

function togglePasswordVisibility(button) {
  var field = button.closest('.password-field');
  var input = field ? field.querySelector('input') : null;
  if (!input) return;

  var tampil = input.type === 'password';
  var label = tampil ? 'Sembunyikan' : 'Tampilkan';
  input.type = tampil ? 'text' : 'password';
  button.textContent = label;
  button.setAttribute('aria-label', label + ' password');
  button.setAttribute('aria-pressed', String(tampil));
}

document.querySelectorAll('.password-toggle').forEach(function (button) {
  button.addEventListener('click', function () {
    togglePasswordVisibility(button);
  });
});

// Pencarian: sembunyikan baris yang kolom pertamanya tidak cocok
var search = document.getElementById('search-input');
if (search) {
  search.addEventListener('input', function () {
    var kata = search.value.trim().toLowerCase();
    document.querySelectorAll('tbody tr').forEach(function (tr) {
      var td = tr.querySelector('td');
      if (!td || td.colSpan > 1) return;
      tr.style.display = td.textContent.toLowerCase().includes(kata) ? '' : 'none';
    });
  });
}

// Konfirmasi untuk tombol berbahaya
document.querySelectorAll('form[data-konfirmasi]').forEach(function (f) {
  f.addEventListener('submit', function (ev) {
    if (!confirm(f.getAttribute('data-konfirmasi'))) ev.preventDefault();
  });
});
