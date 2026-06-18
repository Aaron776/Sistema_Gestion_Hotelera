document.addEventListener('DOMContentLoaded', function() {
    // 1. Toggle Password Visibility
    const toggles = document.querySelectorAll('.toggle-password');
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    // 2. Real-time Password Requirements Validation
    const newPassword = document.getElementById('newPassword');
    const reqs = {
        length: { el: document.getElementById('req-length'), reg: /.{5,}/ },
        upper:  { el: document.getElementById('req-upper'),  reg: /[A-Z]/ },
        lower:  { el: document.getElementById('req-lower'),  reg: /[a-z]/ },
        number: { el: document.getElementById('req-number'), reg: /[0-9]/ }
    };

    newPassword.addEventListener('input', function() {
        const val = this.value;
        
        Object.keys(reqs).forEach(key => {
            const isValid = reqs[key].reg.test(val);
            const icon = reqs[key].el.querySelector('i');
            
            if (isValid) {
                reqs[key].el.classList.add('req-met');
                icon.classList.replace('fa-circle', 'fa-check-circle');
            } else {
                reqs[key].el.classList.remove('req-met');
                icon.classList.replace('fa-check-circle', 'fa-circle');
            }
        });
    });

    // 3. Form Submit Loading State
    const form = document.getElementById('passwordForm');
    form.addEventListener('submit', () => document.getElementById('submitBtn').disabled = true);
});