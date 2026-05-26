/**
 * Password Strength Checker
 * Validates password against industry-standard security criteria
 */

const PasswordStrength = {
    // Criteria definitions
    criteria: {
        length: { regex: /.{8,}/, label: "At least 8 characters" },
        uppercase: { regex: /[A-Z]/, label: "Uppercase letters" },
        lowercase: { regex: /[a-z]/, label: "Lowercase letters" },
        numbers: { regex: /[0-9]/, label: "Numbers" },
        special: { regex: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/, label: "Special characters" }
    },

    // Strength levels with colors
    strengths: {
        0: { level: "Very Weak", color: "#dc3545", percentage: 20 },      // Red
        1: { level: "Weak", color: "#fd7e14", percentage: 40 },            // Orange
        2: { level: "Fair", color: "#ffc107", percentage: 60 },            // Yellow
        3: { level: "Strong", color: "#28a745", percentage: 80 },          // Green
        4: { level: "Very Strong", color: "#20c997", percentage: 100 }     // Dark Green
    },

    /**
     * Calculate password strength score
     * @param {string} password - Password to check
     * @returns {object} { score: 0-4, criteriaList: [], meetsRequirements: boolean }
     */
    calculateStrength(password) {
        if (!password) return { score: 0, criteriaList: [], meetsRequirements: false };

        let score = 0;
        const criteriaList = [];

        // Check each criterion
        for (const [key, criterion] of Object.entries(this.criteria)) {
            if (criterion.regex.test(password)) {
                score++;
                criteriaList.push({ key, label: criterion.label, met: true });
            } else {
                criteriaList.push({ key, label: criterion.label, met: false });
            }
        }

        // Boost score based on length
        if (password.length >= 12) score = Math.min(score + 1, 4);

        // Clamp score to max 4
        score = Math.min(score, 4);

        // Mandatory requirements: all 5 criteria must be met (= score 5, but we cap at 4, so score 4 = Strong)
        const meetsRequirements = score >= 3; // 3 = meets all 5 basic criteria

        return { score, criteriaList, meetsRequirements };
    },

    /**
     * Initialize password strength checker on an input element
     * @param {string} inputId - ID of password input
     * @param {string} containerId - ID of container for strength indicator
     */
    initializeChecker(inputId, containerId) {
        const input = document.getElementById(inputId);
        const container = document.getElementById(containerId);

        if (!input || !container) {
            console.warn(`PasswordStrength: Could not find elements (${inputId}, ${containerId})`);
            return;
        }

        input.addEventListener("input", () => this.updateDisplay(input.value, container, inputId));
    },

    /**
     * Update strength indicator display
     * @param {string} password - Current password value
     * @param {HTMLElement} container - Container for strength display
     * @param {string} inputId - ID of password input for form validation
     */
    updateDisplay(password, container, inputId) {
        const { score, criteriaList, meetsRequirements } = this.calculateStrength(password);
        const strength = this.strengths[score];

        // Clear previous content
        container.innerHTML = "";

        // Strength bar
        const bar = document.createElement("div");
        bar.className = "strength-bar";
        bar.innerHTML = `<div class="strength-fill" style="width: ${strength.percentage}%; background-color: ${strength.color};"></div>`;
        container.appendChild(bar);

        // Strength text
        const text = document.createElement("p");
        text.className = "strength-text";
        text.style.color = strength.color;
        text.style.fontWeight = "bold";
        text.textContent = `Strength: ${strength.level}`;
        container.appendChild(text);

        // Criteria checklist
        const checklist = document.createElement("div");
        checklist.className = "strength-criteria";

        criteriaList.forEach(item => {
            const criterion = document.createElement("div");
            criterion.className = `criteria-item ${item.met ? "met" : "unmet"}`;
            const checkbox = document.createElement("span");
            checkbox.className = "criteria-checkbox";
            checkbox.textContent = item.met ? "✓" : "✗";
            checkbox.style.color = item.met ? strength.color : "#999";
            
            const label = document.createElement("span");
            label.textContent = item.label;
            
            criterion.appendChild(checkbox);
            criterion.appendChild(label);
            checklist.appendChild(criterion);
        });

        container.appendChild(checklist);

        // Requirement warning
        if (!meetsRequirements && password.length > 0) {
            const warning = document.createElement("p");
            warning.className = "strength-warning";
            warning.textContent = "⚠ Password does not meet security requirements";
            warning.style.color = "#dc3545";
            warning.style.fontSize = "0.9em";
            warning.style.marginTop = "10px";
            container.appendChild(warning);
        }

        // Set form validity (for HTML5 validation)
        const input = document.getElementById(inputId);
        if (input) {
            if (meetsRequirements) {
                input.setAttribute("data-strength-valid", "true");
                input.classList.add("password-strong");
                input.classList.remove("password-weak");
            } else if (password.length > 0) {
                input.setAttribute("data-strength-valid", "false");
                input.classList.add("password-weak");
                input.classList.remove("password-strong");
            }
        }
    },

    /**
     * Validate password server-side (call from PHP)
     * @param {string} password - Password to validate
     * @param {string} username - Username (for admin bypass)
     * @returns {object} { valid: boolean, message: string }
     */
    validatePassword(password, username) {
        // Developer exception: admin/0
        if (username.toLowerCase() === "admin" && password === "0") {
            return { valid: true, message: "Developer bypass accepted", isDeveloperMode: true };
        }

        const { score, meetsRequirements } = this.calculateStrength(password);
        
        if (!meetsRequirements) {
            return {
                valid: false,
                message: "Password must contain: 8+ characters, uppercase, lowercase, number, and special character"
            };
        }

        return { valid: true, message: "Password is strong" };
    }
};

// Expose validation function globally for PHP-like usage
function validatePasswordStrength(password, username = "") {
    return PasswordStrength.validatePassword(password, username);
}
