document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("scroll-to-top");
  if (!btn) return;

  const toggle = () => {
    if (window.scrollY > 200) btn.classList.remove("hidden");
    else btn.classList.add("hidden");
  };

  window.addEventListener("scroll", toggle, { passive: true });
  btn.addEventListener("click", () =>
    window.scrollTo({ top: 0, behavior: "smooth" })
  );
  toggle();
});
