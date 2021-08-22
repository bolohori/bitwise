document.addEventListener("DOMContentLoaded", async () => {
  const initialJsonEl = document.querySelector('.import-export-section>textarea');
  initialJsonEl.style.display = 'none';

  let container = document.createElement("div");
  container.id = "jsoneditor";
  container.style.height = '400px';

  document.querySelector('.import-export-section').appendChild(container);

  const options = {
    mode: 'code',
    modes: ['code', 'tree'],
    onError: function (err) {
      alert(err.toString())
    }
  }

  const editor = new JSONEditor(container, options, JSON.parse(initialJsonEl.value));
});
