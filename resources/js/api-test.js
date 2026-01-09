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

async function testApiKeyById(id, btn) {
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
      body: JSON.stringify({ id }),
    });
    const result = await res.json();
    if (result.status === "active") {
      toast(`API Key #${id}: Working properly`, "success");
    } else {
      toast(`API Key #${id}: ${result.error || "Failed to connect"}`, "error");
    }
    setTimeout(() => window.location.reload(), 1000);
  } catch (e) {
    toast(`Error testing API Key #${id}: ${e.message}`, "error");
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
function openKeyModal(mode = "create", data = {}) {
  const modal = document.getElementById("key-modal");
  const title = document.getElementById("key-modal-title");
  const form = document.getElementById("key-form");

  document.getElementById("key_mode").value = mode;
  document.getElementById("key_id").value = data.id || "";
  document.getElementById("key_label").value = data.label || "";
  document.getElementById("key_email").value = data.email || "";
  document.getElementById("key_value").value = ""; // jangan prefill key untuk keamanan

  title.textContent = mode === "edit" ? `Edit Key #${data.id}` : "Add Key";
  modal.classList.remove("hidden");
}

function closeKeyModal() {
  document.getElementById("key-modal").classList.add("hidden");
}

async function submitKeyForm(e) {
  console.log("Submitting to", url, "with payload", payload);
  return;
  e.preventDefault();
  const btn = document.getElementById("key-submit");
  const restore = setLoading(
    btn,
    '<i class="fas fa-spinner fa-spin mr-1"></i>Saving...'
  );

  const mode = document.getElementById("key_mode").value;
  const id = document.getElementById("key_id").value;

  const payload = {
    label: document.getElementById("key_label").value,
    account_email: document.getElementById("key_email").value,
    key: document.getElementById("key_value").value,
  };

  try {
    const url =
      mode === "edit"
        ? `/admin/api-monitor/keys/${id}`
        : `/admin/api-monitor/keys`;
    const method = mode === "edit" ? "PUT" : "POST";

    const res = await fetch(url, {
      method,
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrf(),
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });

    const json = await res.json().catch(() => ({}));

    if (!res.ok) {
      // 423: locked karena GOOD
      if (res.status === 423) {
        showKeyError(
          json.message || "Key dikunci (GOOD). Disable dulu.",
          "warning"
        );
        return;
      }

      // 422: validation
      if (res.status === 422) {
        showKeyError(json.message || "Validasi gagal. Cek input.", "error");
        showFieldErrors(json.errors);
        return;
      }

      // lainnya
      showKeyError(json.message || "Terjadi error pada server.", "error");
      return;
    }

    showKeyError(json.message || "Berhasil menyimpan key", "success");

    closeKeyModal();
    location.reload();
  } catch (err) {
    showKeyError(json.message || "Error saat menyimpan", "error");
  } finally {
    restore();
  }
}

async function deleteKey(id, btn) {
  if (!confirm("Yakin hapus key ini?")) return;

  const restore = setLoading(
    btn,
    '<i class="fas fa-spinner fa-spin mr-1"></i>Deleting...'
  );

  try {
    const res = await fetch(`/admin/api-monitor/keys/${id}`, {
      method: "DELETE",
      headers: {
        "X-CSRF-TOKEN": csrf(),
        Accept: "application/json",
      },
    });

    const json = await res.json().catch(() => ({}));

    if (!res.ok) {
      toast(json.message || "Gagal menghapus key", "error");
      restore();
      return;
    }

    toast(json.message || "Key terhapus", "success");
    location.reload();
  } catch (err) {
    toast(err?.message || "Error saat hapus", "error");
  } finally {
    restore();
  }
}
function clearKeyErrors() {
  const box = document.getElementById("key-form-error");
  box.className = "hidden p-3 rounded-md border text-sm";
  box.textContent = "";

  ["key_label", "key_email", "key_value"].forEach((id) => {
    const el = document.getElementById("err_" + id);
    if (el) {
      el.classList.add("hidden");
      el.textContent = "";
    }
  });
}

function showKeyError(message, type = "error") {
  const box = document.getElementById("key-form-error");
  box.classList.remove("hidden");
  box.classList.add(
    type === "warning"
      ? "bg-yellow-50 border-yellow-200 text-yellow-800"
      : "bg-red-50 border-red-200 text-red-800"
  );
  box.textContent = message;
}

function showFieldErrors(errors) {
  // errors: { field: ["msg1", ...], ... }
  const map = {
    label: "err_key_label",
    account_email: "err_key_email",
    key: "err_key_value",
  };

  Object.keys(errors || {}).forEach((field) => {
    const id = map[field];
    if (!id) return;
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = errors[field][0];
    el.classList.remove("hidden");
  });
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
      const id = Number(btn.dataset.id);
      if (Number.isFinite(id)) await testApiKeyById(id, btn);
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

  document.addEventListener("click", (e) => {
    const t = e.target.closest("[data-action], .js-edit-key, .js-delete-key");
    if (!t) return;

    // Add Key
    if (t.dataset.action === "openKeyModal") {
      openKeyModal("create");
      return;
    }
    if (t.dataset.action === "closeKeyModal") {
      closeKeyModal();
      return;
    }

    // Edit
    if (t.classList.contains("js-edit-key")) {
      openKeyModal("edit", {
        id: t.dataset.id,
        label: t.dataset.label,
        email: t.dataset.email,
      });
      return;
    }

    // Delete
    if (t.classList.contains("js-delete-key")) {
      deleteKey(t.dataset.id, t);
      return;
    }
  });

  document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("key-form");
    if (form) form.addEventListener("submit", submitKeyForm);
  });
}
