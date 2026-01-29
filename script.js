document.addEventListener('DOMContentLoaded', () => {

    // Password Toggle Logic
    const togglePasswordBtns = document.querySelectorAll('.toggle-password');
    togglePasswordBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const inputGroup = btn.closest('.input-group');
            // If the structure is strictly input + label + highlight + button in a group
            // We can search within the group
            const passwordInput = inputGroup.querySelector('input');
            const eyeIcon = btn.querySelector('svg');

            if (!passwordInput) return; // Safety check

            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            if (type === 'text') {
                eyeIcon.style.opacity = '0.5';
                eyeIcon.innerHTML = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>`;
            } else {
                eyeIcon.style.opacity = '1';
                eyeIcon.innerHTML = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>`;
            }
        });
    });

    // Ripple Effect (Visual Only)
    const btns = document.querySelectorAll('.login-btn');
    btns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            // No prevention of default here, let the form submit to PHP
        });
    });
});
