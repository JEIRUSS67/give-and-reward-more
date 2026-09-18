(function () {
  "use strict";

  var items = Array.prototype.slice.call(document.querySelectorAll(".gallery-item"));
  var lightbox = document.querySelector(".lightbox");
  if (!items.length || !lightbox) return;

  var img = lightbox.querySelector("img");
  var caption = lightbox.querySelector(".lightbox__caption");
  var closeBtn = lightbox.querySelector(".lightbox__close");
  var prevBtn = lightbox.querySelector(".lightbox__prev");
  var nextBtn = lightbox.querySelector(".lightbox__next");
  var currentIndex = 0;
  var lastFocused = null;

  function show(index) {
    currentIndex = (index + items.length) % items.length;
    var item = items[currentIndex];
    var fullSrc = item.getAttribute("data-full") || item.querySelector("img").src;
    var alt = item.querySelector("img").alt;
    img.src = fullSrc;
    img.alt = alt;
    caption.textContent = alt;
  }

  function open(index) {
    lastFocused = document.activeElement;
    show(index);
    lightbox.classList.add("is-open");
    document.body.style.overflow = "hidden";
    closeBtn.focus();
  }

  function close() {
    lightbox.classList.remove("is-open");
    document.body.style.overflow = "";
    if (lastFocused) lastFocused.focus();
  }

  items.forEach(function (item, index) {
    item.addEventListener("click", function () { open(index); });
  });

  closeBtn.addEventListener("click", close);
  prevBtn.addEventListener("click", function () { show(currentIndex - 1); });
  nextBtn.addEventListener("click", function () { show(currentIndex + 1); });

  lightbox.addEventListener("click", function (e) {
    if (e.target === lightbox) close();
  });

  document.addEventListener("keydown", function (e) {
    if (!lightbox.classList.contains("is-open")) return;
    if (e.key === "Escape") close();
    if (e.key === "ArrowLeft") show(currentIndex - 1);
    if (e.key === "ArrowRight") show(currentIndex + 1);
  });
})();
