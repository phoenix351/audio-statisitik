export function initCharCounter({ textareaId, counterId, warnAt = 900 }) {
  const ta = document.getElementById(textareaId);
  const counter = document.getElementById(counterId);
  if (!ta || !counter) return;

  const set = (v) => {
    counter.textContent = v.length;
    counter.classList.toggle("text-red-500", v.length > warnAt);
    counter.classList.toggle("text-gray-400", v.length <= warnAt);
  };

  set(ta.value ?? "");
  ta.addEventListener("input", () => set(ta.value ?? ""));
}
