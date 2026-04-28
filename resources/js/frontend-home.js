const translations = {
    en: {
        brand_tagline: "Academic Code Analysis Platform",
        nav_features: "Features",
        nav_workflow: "Workflow",
        nav_preview: "Preview",
        nav_start: "Start Analysis",
        hero_badge: "NEXT-GENERATION PROJECT ANALYSIS EXPERIENCE",
        hero_title: "Analyze code, understand projects, and elevate quality.",
        hero_text:
            "Transform source code into clear insights, structured reports, and confident academic evaluation.",
        hero_cta_primary: "Start Analysis",
        hero_cta_secondary: "View Report",
        hero_meta_1: "Focused scope",
        hero_meta_2: "Privacy-first",
        hero_meta_3: "Fast workflow",
    },
    ar: {
        brand_tagline: "منصة تحليل أكواد المشاريع",
        nav_features: "المميزات",
        nav_workflow: "الآلية",
        nav_preview: "المعاينة",
        nav_start: "ابدأ الفحص",
        hero_badge: "تحليل مشاريع متقدم",
        hero_title: "حلّل الكود وارفع جودة المشروع بسهولة.",
        hero_text:
            "تحويل الكود إلى تقارير واضحة ومخرجات احترافية تدعم التقييم الأكاديمي.",
        hero_cta_primary: "ابدأ الفحص",
        hero_cta_secondary: "عرض التقرير",
        hero_meta_1: "فحص مركز",
        hero_meta_2: "خصوصية عالية",
        hero_meta_3: "سريع وذكي",
    },
};

document.addEventListener("DOMContentLoaded", () => {
    const root = document.getElementById("vgHome");
    const themeToggle = document.getElementById("vgThemeToggle");
    const themeIcon = document.getElementById("vgThemeIcon");
    const langToggle = document.getElementById("vgLangToggle");

    const savedTheme = localStorage.getItem("vg-home-theme") || "dark";
    const savedLang = localStorage.getItem("vg-home-lang") || "en";

    /* =========================
       BACKGROUND (optimized)
    ========================= */
    function injectAnimatedBackground() {
        if (document.getElementById("vgDynamicBg")) return;

        const bg = document.createElement("div");
        bg.id = "vgDynamicBg";
        bg.innerHTML = `
            <div class="vg-orb orb-1"></div>
            <div class="vg-orb orb-2"></div>
            <div class="vg-orb orb-3"></div>
        `;

        document.body.prepend(bg);
    }

    /* =========================
       THEME
    ========================= */
    function applyTheme(theme) {
        document.body.setAttribute("data-theme", theme);
        if (themeIcon) {
            themeIcon.textContent = theme === "dark" ? "☀" : "🌙";
        }
        localStorage.setItem("vg-home-theme", theme);
    }

    /* =========================
       LANGUAGE (SAFE + CLEAN)
    ========================= */
    function applyLanguage(lang) {
        if (root) root.setAttribute("data-lang", lang);

        document.documentElement.lang = lang === "ar" ? "ar" : "en";
        document.documentElement.dir = lang === "ar" ? "rtl" : "ltr";

        document.querySelectorAll("[data-i18n]").forEach((el) => {
            const key = el.dataset.i18n;

            const value =
                translations[lang]?.[key] ??
                translations["en"]?.[key] ??
                key;

            el.textContent = value;
        });

        if (langToggle) {
            const span = langToggle.querySelector("span");
            if (span) span.textContent = lang === "en" ? "AR" : "EN";
        }

        localStorage.setItem("vg-home-lang", lang);
    }

    /* =========================
       INIT
    ========================= */
    injectAnimatedBackground();
    applyTheme(savedTheme);
    applyLanguage(savedLang);

    /* =========================
       EVENTS
    ========================= */
    themeToggle?.addEventListener("click", () => {
        const current = document.body.getAttribute("data-theme") || "dark";
        applyTheme(current === "dark" ? "light" : "dark");
    });

    langToggle?.addEventListener("click", () => {
        const current = root?.getAttribute("data-lang") || "en";
        applyLanguage(current === "en" ? "ar" : "en");
    });

    /* =========================
       REVEAL ANIMATION
    ========================= */
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                }
            });
        },
        { threshold: 0.15 }
    );

    document.querySelectorAll(".reveal").forEach((el) => observer.observe(el));
});