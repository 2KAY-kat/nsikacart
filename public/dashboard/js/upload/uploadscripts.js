        document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('photos').addEventListener('change', function(e) {
    const fileName = e.target.files.length > 0 ? e.target.files[0].name : 'No files chosen';
            document.querySelector('.file-name').textContent = fileName;
  });
});