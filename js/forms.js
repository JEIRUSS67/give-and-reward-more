(function () {
  "use strict";

  /* Client-side validation is a courtesy to the visitor only.
     The PHP endpoints (php/contact.php, php/support.php) re-validate
     everything server-side and must never trust this layer alone. */

  function setError(field, message) {
    field.classList.toggle("has-error", Boolean(message));
    var errorEl = field.querySelector(".field-error");
    if (errorEl) errorEl.textContent = message || "";
  }

  function validateField(field) {
    var input = field.querySelector("input, textarea, select");
    if (!input) return true;

    if (input.hasAttribute("required") && !input.value.trim()) {
      setError(field, "This field is required.");
      return false;
    }
    if (input.type === "email" && input.value.trim()) {
      var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailPattern.test(input.value.trim())) {
        setError(field, "Enter a valid email address.");
        return false;
      }
    }
    if (input.type === "tel" && input.value.trim()) {
      var phonePattern = /^[0-9+\-\s()]{6,20}$/;
      if (!phonePattern.test(input.value.trim())) {
        setError(field, "Enter a valid phone number.");
        return false;
      }
    }
    setError(field, "");
    return true;
  }

  function validateForm(form) {
    var fields = form.querySelectorAll(".field");
    var valid = true;
    fields.forEach(function (field) {
      if (!validateField(field)) valid = false;
    });
    return valid;
  }

  function showStatus(form, type, message) {
    var status = form.querySelector(".form-status");
    if (!status) return;
    status.textContent = message;
    status.classList.remove("form-status--success", "form-status--error");
    status.classList.add("is-visible", type === "success" ? "form-status--success" : "form-status--error");
    status.setAttribute("role", "status");
    status.focus && status.focus();
  }

  function handleSubmit(form, endpoint) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      if (!validateForm(form)) {
        showStatus(form, "error", "Please correct the highlighted fields and try again.");
        return;
      }

      var submitBtn = form.querySelector("button[type='submit']");
      var originalLabel = submitBtn ? submitBtn.textContent : "";
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = "Sending...";
      }

      var formData = new FormData(form);

      fetch(endpoint, {
        method: "POST",
        body: formData,
        headers: { "X-Requested-With": "XMLHttpRequest" },
      })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (data && data.success) {
            showStatus(form, "success", data.message || "Thank you. Your message has been sent.");
            form.reset();
          } else {
            showStatus(form, "error", (data && data.message) || "Something went wrong. Please try again.");
          }
        })
        .catch(function () {
          showStatus(form, "error", "We could not reach the server. Please try again in a moment.");
        })
        .finally(function () {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalLabel;
          }
        });
    });

    form.querySelectorAll(".field input, .field textarea, .field select").forEach(function (input) {
      input.addEventListener("blur", function () {
        validateField(input.closest(".field"));
      });
    });
  }

  var contactForm = document.getElementById("contact-form");
  if (contactForm) handleSubmit(contactForm, "php/contact.php");

  var supportForm = document.getElementById("support-form");
  if (supportForm) handleSubmit(supportForm, "php/support.php");
})();
