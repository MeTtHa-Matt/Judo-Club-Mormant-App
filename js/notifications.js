(function () {
    "use strict";

    var JCM_ICONS = {
        success: "bi-check-circle-fill",
        alert: "bi-exclamation-triangle-fill",
        info: "bi-info-circle-fill",
    };

    var JCM_LABELS = {
        success: "Succès",
        alert: "Attention",
        info: "Information",
    };

    document.addEventListener("DOMContentLoaded", function () {
        var container = document.getElementById("jcm-toast-container");
        if (!container) return;

        Array.prototype.slice
            .call(container.querySelectorAll(".jcm-toast"))
            .forEach(initToast);
    });

    function getContainer() {
        var container = document.getElementById("jcm-toast-container");
        if (!container) {
            container = document.createElement("div");
            container.id = "jcm-toast-container";
            container.className = "jcm-toast-container";
            container.setAttribute("aria-live", "polite");
            container.setAttribute("aria-atomic", "true");
            document.body.appendChild(container);
        }
        return container;
    }

    /**
     * @param {string} type
     * @param {string} message
     * @param {string} [title]
     * @param {number} [duration]
     */
    function jcmToast(type, message, title, duration) {
        if (type === "error" || type === "danger") type = "alert";
        if (!JCM_ICONS[type]) type = "info";
        duration = duration || 6000;

        var container = getContainer();

        var toast = document.createElement("div");
        toast.className = "jcm-toast jcm-toast-" + type;
        toast.setAttribute("role", "status");
        toast.setAttribute("data-autohide", duration);

        toast.innerHTML =
            '<div class="jcm-toast-icon"><i class="bi ' + JCM_ICONS[type] + '"></i></div>' +
            '<div class="jcm-toast-body">' +
            '<p class="jcm-toast-title"></p>' +
            '<p class="jcm-toast-message"></p>' +
            "</div>" +
            '<button type="button" class="jcm-toast-close" aria-label="Fermer la notification"><i class="bi bi-x-lg"></i></button>' +
            '<div class="jcm-toast-progress"></div>';

        toast.querySelector(".jcm-toast-title").textContent = title || JCM_LABELS[type];
        toast.querySelector(".jcm-toast-message").textContent = message;

        container.appendChild(toast);
        initToast(toast);

        return toast;
    }

    function initToast(toast) {
        requestAnimationFrame(function () {
            toast.classList.add("jcm-toast-show");
        });

        var duration = parseInt(toast.getAttribute("data-autohide"), 10) || 6000;
        var progress = toast.querySelector(".jcm-toast-progress");
        var hideTimer, remaining = duration, start;

        function startTimer(time) {
            start = Date.now();
            if (progress) {
                progress.style.transition = "width " + time + "ms linear";
                requestAnimationFrame(function () {
                    progress.style.width = "0%";
                });
            }
            hideTimer = setTimeout(function () {
                closeToast(toast);
            }, time);
        }

        function pauseTimer() {
            clearTimeout(hideTimer);
            remaining -= Date.now() - start;
            if (progress) {
                var computed = getComputedStyle(progress).width;
                progress.style.transition = "none";
                progress.style.width = computed;
            }
        }

        if (progress) progress.style.width = "100%";
        startTimer(duration);

        toast.addEventListener("mouseenter", pauseTimer);
        toast.addEventListener("mouseleave", function () {
            if (remaining > 200) startTimer(remaining);
        });

        var closeBtn = toast.querySelector(".jcm-toast-close");
        if (closeBtn) {
            closeBtn.addEventListener("click", function () {
                clearTimeout(hideTimer);
                closeToast(toast);
            });
        }

        var startX = null;

        toast.addEventListener(
            "touchstart",
            function (e) {
                startX = e.touches[0].clientX;
                pauseTimer();
            },
            { passive: true }
        );

        toast.addEventListener(
            "touchmove",
            function (e) {
                if (startX === null) return;
                var deltaX = e.touches[0].clientX - startX;
                toast.style.transform = "translateX(" + deltaX + "px)";
                toast.style.opacity = Math.max(0, 1 - Math.abs(deltaX) / 150);
            },
            { passive: true }
        );

        toast.addEventListener("touchend", function (e) {
            if (startX === null) return;
            var deltaX = e.changedTouches[0].clientX - startX;
            if (Math.abs(deltaX) > 100) {
                closeToast(toast);
            } else {
                toast.style.transform = "";
                toast.style.opacity = "";
                if (remaining > 200) startTimer(remaining);
            }
            startX = null;
        });
    }

    function closeToast(toast) {
        toast.classList.remove("jcm-toast-show");
        toast.classList.add("jcm-toast-hide");
        setTimeout(function () {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 350);
    }

    window.jcmToast = jcmToast;
})();
