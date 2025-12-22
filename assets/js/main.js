// Bootstrap specific functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize Bootstrap popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Form validation with Bootstrap
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Date picker restrictions
    const today = new Date().toISOString().split('T')[0];
    document.querySelectorAll('input[type="date"]').forEach(input => {
        if (!input.min) {
            input.min = today;
        }
    });
    
    // Room booking calculator
    const calculateRoomTotal = () => {
        const pricePerNight = document.getElementById('pricePerNight')?.value;
        const nights = document.getElementById('nights')?.value;
        const totalElement = document.getElementById('totalPrice');
        
        if (pricePerNight && nights && totalElement) {
            const total = pricePerNight * nights;
            totalElement.textContent = `$${total.toFixed(2)}`;
        }
    };
    
    // Attach event listeners for calculation
    document.getElementById('nights')?.addEventListener('change', calculateRoomTotal);
    document.getElementById('checkIn')?.addEventListener('change', function() {
        const checkIn = new Date(this.value);
        const checkOut = document.getElementById('checkOut');
        if (checkOut && this.value) {
            const minDate = new Date(checkIn);
            minDate.setDate(minDate.getDate() + 1);
            checkOut.min = minDate.toISOString().split('T')[0];
        }
    });
    
    // Image gallery for rooms
    const roomImages = document.querySelectorAll('.room-gallery img');
    const mainImage = document.getElementById('mainRoomImage');
    
    if (roomImages.length > 0 && mainImage) {
        roomImages.forEach(img => {
            img.addEventListener('click', function() {
                mainImage.src = this.src;
                roomImages.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }
    
    // Mobile menu improvements
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            const navbarCollapse = document.getElementById('navbarNav');
            if (navbarCollapse.classList.contains('show')) {
                const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                bsCollapse.hide();
            }
        });
    });
});