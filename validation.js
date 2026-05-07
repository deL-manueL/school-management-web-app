/*
 * Shared client-side form validation for the IPMC project.
 * Exposes window.IPMCValidation = { rules, validateField, attach }.
 *
 * Errors render inline as <span class="field-error"> directly after the input.
 * Inputs receive a red border on error. Native browser validation popups are
 * suppressed (form gets `novalidate`) so all messages are inline and consistent.
 */
(function (global) {
    'use strict';

    const FIELD_ERROR_CLASS = 'field-error';
    const INPUT_ERROR_CLASS = 'ipmc-input-error';

    const rules = {
        required(value, input) {
            if (input && input.type === 'checkbox') {
                return input.checked ? null : 'This field is required.';
            }
            if (input && input.tagName === 'SELECT') {
                return value && value.trim() !== '' ? null : 'Please select an option.';
            }
            return value && value.trim() !== '' ? null : 'This field is required.';
        },

        email(value) {
            if (!value) return null;
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())
                ? null
                : 'Please enter a valid email address.';
        },

        phone(value) {
            if (!value) return null;
            const v = value.trim();
            if (!/^\d+$/.test(v)) return 'Phone number must contain digits only — no spaces, dashes, or letters.';
            if (v.length < 10) return 'Phone number must be at least 10 digits.';
            if (v.length > 15) return 'Phone number must be at most 15 digits.';
            return null;
        },

        name(value) {
            if (!value) return null;
            return /^[A-Za-z][A-Za-z\s'\-]*$/.test(value.trim())
                ? null
                : 'Only letters, spaces, hyphens, and apostrophes are allowed.';
        },

        studentId(value) {
            if (!value) return null;
            return /^IPMC\d{4}\d{3}$/.test(value.trim())
                ? null
                : 'Student ID must match the format IPMCYYYYNNN (e.g., IPMC2026001).';
        },

        passwordMin(value) {
            if (!value) return null;
            return value.length >= 8 ? null : 'Password must be at least 8 characters.';
        },

        integer(value) {
            if (!value) return null;
            return /^\d+$/.test(value.trim()) ? null : 'Only whole numbers (digits) are allowed.';
        },

        integerInRange(min, max) {
            return function (value) {
                if (!value) return null;
                const v = value.trim();
                if (!/^\d+$/.test(v)) return 'Only whole numbers (digits) are allowed.';
                const n = parseInt(v, 10);
                if (n < min) return `Value must be at least ${min}.`;
                if (n > max) return `Value must be at most ${max}.`;
                return null;
            };
        },

        decimal(value) {
            if (!value) return null;
            return /^\d+(\.\d+)?$/.test(value.trim())
                ? null
                : 'Enter a valid number (digits, optional decimal).';
        },

        decimalInRange(min, max) {
            return function (value) {
                if (!value) return null;
                const v = value.trim();
                if (!/^\d+(\.\d+)?$/.test(v)) return 'Enter a valid number.';
                const n = parseFloat(v);
                if (n < min) return `Value must be at least ${min}.`;
                if (n > max) return `Value must be at most ${max}.`;
                return null;
            };
        },

        academicYear(value) {
            if (!value) return null;
            const v = value.trim();
            const m = v.match(/^(\d{4})\/(\d{4})$/);
            if (!m) return 'Use the format YYYY/YYYY (e.g., 2024/2025).';
            const a = parseInt(m[1], 10), b = parseInt(m[2], 10);
            if (b !== a + 1) return 'Second year must be one greater than the first (e.g., 2024/2025).';
            return null;
        },

        match(otherSelector, label) {
            return function (value, input) {
                const other = input.form ? input.form.querySelector(otherSelector) : null;
                if (!other) return null;
                return value === other.value ? null : `${label || 'Fields'} do not match.`;
            };
        },
    };

    function ensureErrorEl(input) {
        let next = input.nextElementSibling;
        while (next) {
            if (next.classList && next.classList.contains(FIELD_ERROR_CLASS)) return next;
            if (next.classList && next.classList.contains('password-strength')) {
                next = next.nextElementSibling;
                continue;
            }
            if (next.classList && next.classList.contains('password-strength-label')) {
                next = next.nextElementSibling;
                continue;
            }
            break;
        }
        const span = document.createElement('span');
        span.className = FIELD_ERROR_CLASS;
        span.style.display = 'none';
        span.style.color = '#c33';
        span.style.fontSize = '12px';
        span.style.marginTop = '4px';
        span.style.lineHeight = '1.4';
        span.setAttribute('role', 'alert');
        const anchor = findInsertAnchor(input);
        anchor.insertAdjacentElement('afterend', span);
        return span;
    }

    function findInsertAnchor(input) {
        let anchor = input;
        let next = anchor.nextElementSibling;
        while (next && next.classList && (
            next.classList.contains('password-strength') ||
            next.classList.contains('password-strength-label')
        )) {
            anchor = next;
            next = anchor.nextElementSibling;
        }
        return anchor;
    }

    function setError(input, message) {
        const errEl = ensureErrorEl(input);
        if (message) {
            errEl.textContent = message;
            errEl.style.display = 'block';
            input.style.borderColor = '#c33';
            input.classList.add(INPUT_ERROR_CLASS);
            input.setAttribute('aria-invalid', 'true');
        } else {
            errEl.textContent = '';
            errEl.style.display = 'none';
            input.style.borderColor = '';
            input.classList.remove(INPUT_ERROR_CLASS);
            input.removeAttribute('aria-invalid');
        }
    }

    function validateField(input, fieldRules) {
        if (!input) return null;
        const value = input.type === 'checkbox' ? (input.checked ? 'on' : '') : input.value;
        const ruleList = Array.isArray(fieldRules) ? fieldRules : [fieldRules];
        for (const ruleFn of ruleList) {
            const message = ruleFn(value, input);
            if (message) {
                setError(input, message);
                return message;
            }
        }
        setError(input, null);
        return null;
    }

    function ensureStrengthIndicator(input) {
        let next = input.nextElementSibling;
        if (next && next.classList && next.classList.contains('password-strength')) return next;

        const bar = document.createElement('div');
        bar.className = 'password-strength';
        bar.style.height = '4px';
        bar.style.borderRadius = '4px';
        bar.style.marginTop = '6px';
        bar.style.background = '#eee';
        bar.style.overflow = 'hidden';

        const fill = document.createElement('div');
        fill.className = 'password-strength-fill';
        fill.style.height = '100%';
        fill.style.width = '0%';
        fill.style.transition = 'width 0.2s, background 0.2s';
        bar.appendChild(fill);

        const label = document.createElement('span');
        label.className = 'password-strength-label';
        label.style.fontSize = '11px';
        label.style.color = '#666';
        label.style.display = 'block';
        label.style.marginTop = '4px';
        label.textContent = '';

        input.insertAdjacentElement('afterend', label);
        input.insertAdjacentElement('afterend', bar);
        return bar;
    }

    function updateStrength(input) {
        const bar = ensureStrengthIndicator(input);
        const fill = bar.querySelector('.password-strength-fill');
        const label = bar.nextElementSibling;
        const value = input.value || '';
        let score = 0;
        if (value.length >= 8) score++;
        if (/[A-Z]/.test(value)) score++;
        if (/[a-z]/.test(value)) score++;
        if (/\d/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;

        const levels = [
            { w: '0%',   c: '#eee',    t: '' },
            { w: '20%',  c: '#e74c3c', t: 'Very weak' },
            { w: '40%',  c: '#e67e22', t: 'Weak' },
            { w: '60%',  c: '#f1c40f', t: 'Fair' },
            { w: '80%',  c: '#27ae60', t: 'Strong' },
            { w: '100%', c: '#16a085', t: 'Very strong' },
        ];
        const level = levels[Math.min(score, 5)];
        fill.style.width = level.w;
        fill.style.background = level.c;
        if (label && label.classList && label.classList.contains('password-strength-label')) {
            label.textContent = level.t;
            label.style.color = level.c;
        }
    }

    function isPasswordStrengthRule(rule) {
        return rule === rules.passwordMin;
    }

    function attach(form, fieldRules) {
        if (!form) return;
        form.setAttribute('novalidate', 'novalidate');

        const selectors = Object.keys(fieldRules);

        selectors.forEach(selector => {
            const input = form.querySelector(selector);
            if (!input) return;
            const ruleList = Array.isArray(fieldRules[selector]) ? fieldRules[selector] : [fieldRules[selector]];

            const event = (input.tagName === 'SELECT' || input.type === 'checkbox') ? 'change' : 'blur';
            input.addEventListener(event, () => validateField(input, ruleList));
            input.addEventListener('input', () => {
                if (input.classList.contains(INPUT_ERROR_CLASS)) {
                    validateField(input, ruleList);
                }
            });

            if (ruleList.some(isPasswordStrengthRule)) {
                ensureStrengthIndicator(input);
                input.addEventListener('input', () => updateStrength(input));
            }
        });

        form.addEventListener('submit', function (e) {
            let firstInvalid = null;
            selectors.forEach(selector => {
                const input = form.querySelector(selector);
                if (!input) return;
                const ruleList = Array.isArray(fieldRules[selector]) ? fieldRules[selector] : [fieldRules[selector]];
                const errMsg = validateField(input, ruleList);
                if (errMsg && !firstInvalid) firstInvalid = input;
            });
            if (firstInvalid) {
                e.preventDefault();
                e.stopImmediatePropagation();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => firstInvalid.focus(), 250);
            }
        }, true);
    }

    global.IPMCValidation = {
        rules,
        validateField,
        attach,
    };
})(window);
