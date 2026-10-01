// Auto-hide alerts after 4 seconds
document.querySelectorAll('.alert').forEach(function (a) {
  setTimeout(function () { bootstrap.Alert.getOrCreateInstance(a).close(); }, 4000);
});
