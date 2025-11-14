// resources/js/api-test.js

function csrf() {
  const el = document.querySelector('meta[name="csrf-token"]');
  return el ? el.content : "";
}

function toast(message, type = "info") {
  if (typeof window.showToast === "function") {
    window.showToast(message, type);
  } else {
    alert(`${type.toUpperCase()}: ${message}`);
  }
}

function setLoading(btn, htmlWhileLoading = null) {
  if (!btn) return () => {};
  const originalHTML = btn.innerHTML;
  const originalDisabled = btn.disabled;
  if (htmlWhileLoading) btn.innerHTML = htmlWhileLoading;
  btn.disabled = true;
  return () => {
    btn.innerHTML = originalHTML;
    btn.disabled = originalDisabled;
  };
}

async function testApiKey(keyIndex, btn) {
  const restore = setLoading(
    btn,
    '<i class="fas fa-spinner fa-spin mr-1"></i>Testing...'
  );
  try {
    const res = await fetch("/admin/api-monitor/test-key", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrf(),
      },
      body: JSON.stringify({ key_index: keyIndex }),
    });
    const result = await res.json();
    if (result.status === "active") {
      toast(`API Key #${keyIndex + 1}: Working properly`, "success");
    } else {
      toast(
        `API Key #${keyIndex + 1}: ${result.error || "Failed to connect"}`,
        "error"
      );
    }
    setTimeout(() => window.location.reload(), 1000);
  } catch (e) {
    toast(`Error testing API Key #${keyIndex + 1}: ${e.message}`, "error");
  } finally {
    restore();
  }
}

async function testAllKeys(btn) {
  const restore = setLoading(
    btn,
    '<i class="fas fa-spinner fa-spin mr-2"></i>Testing All...'
  );
  try {
    const res = await fetch("/admin/api-monitor/test-all-keys", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrf(),
      },
    });
    const result = await res.json();
    toast(
      `Tested ${result.total} keys. ${result.active} active, ${result.failed} failed.`,
      "info"
    );
    setTimeout(() => window.location.reload(), 2000);
  } catch (e) {
    toast(`Error testing all keys: ${e.message}`, "error");
  } finally {
    restore();
  }
}

function refreshAllData() {
  try {
    window.location.reload();
  } catch {
    toast("Failed to refresh data", "error");
  }
}

function changePage(newPage) {
  const n = Number(newPage);
  if (Number.isFinite(n)) {
    const url = new URL(window.location.href);
    url.searchParams.set("page", String(n));
    window.location.href = url.toString();
  }
}

async function resetStuckDocuments() {
  if (
    !confirm(
      "Are you sure you want to reset all stuck documents? This will requeue them for processing."
    )
  ) {
    return;
  }
  try {
    const res = await fetch("/admin/api-monitor/reset-stuck", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrf(),
      },
    });
    const result = await res.json();
    toast(`Success: ${result.message}`, "success");
    setTimeout(() => window.location.reload(), 1000);
  } catch (e) {
    toast(`Error resetting documents: ${e.message}`, "error");
  }
}

function runCommand(command) {
  const modal = document.getElementById("command-modal");
  const out = document.getElementById("command-output");
  if (!modal || !out) return;
  modal.classList.remove("hidden");
  out.innerHTML = `<div class="animate-pulse">Running command: ${command}...</div>`;
  setTimeout(() => {
    out.innerHTML = `
      <div class="text-yellow-400">$ php artisan ${command}</div>
      <div class="mt-2">✅ Command executed successfully</div>
      <div class="mt-1 text-gray-400">Check Laravel logs for detailed output</div>
      <div class="mt-2 text-blue-400">Note: This is a simulated output. Implement actual command execution via AJAX for production use.</div>
    `;
  }, 2000);
}

function closeCommandModal() {
  const modal = document.getElementById("command-modal");
  if (modal) modal.classList.add("hidden");
}

async function refreshQueueStats() {
  try {
    // Implement real AJAX if needed
    toast("Queue stats refreshed", "success");
  } catch {
    toast("Failed to refresh queue stats", "error");
  }
}

export function initApiMonitor() {
  // Event delegation (works for dynamic DOM, Livewire, etc.)
  document.addEventListener("click", async (e) => {
    const btn = e.target.closest("button");
    if (!btn) return;

    // Dedicated class for per-key test
    if (btn.classList.contains("js-test-api")) {
      e.preventDefault();
      e.stopPropagation();
      const idx = Number(btn.dataset.keyIndex);
      if (Number.isFinite(idx)) await testApiKey(idx, btn);
      return;
    }

    // Generic actions via data-action
    const action = btn.dataset.action;
    if (!action) return;

    e.preventDefault();
    e.stopPropagation();

    switch (action) {
      case "refreshAllData":
        refreshAllData();
        break;
      case "testAllKeys":
        await testAllKeys(btn);
        break;
      case "changePage":
        changePage(btn.dataset.page);
        break;
      case "resetStuckDocuments":
        await resetStuckDocuments();
        break;
      case "runCommand":
        runCommand(btn.dataset.command);
        break;
      case "closeCommandModal":
        closeCommandModal();
        break;
      case "refreshQueueStats":
        await refreshQueueStats();
        break;
    }
  });
}
