// Modal Logic
const loginModal = document.getElementById('login-modal');
const registerModal = document.getElementById('register-modal');
const closeModals = document.querySelectorAll('.close-modal');

// Open modals when clicking any element with data-modal attribute
document.querySelectorAll('[data-modal]').forEach(button => {
    button.addEventListener('click', () => {
        const modalId = button.getAttribute('data-modal');
        document.getElementById(modalId).style.display = 'flex';
    });
});

// Close modals
closeModals.forEach((close) => {
    close.addEventListener('click', () => {
        loginModal.style.display = 'none';
        registerModal.style.display = 'none';
    });
});

window.addEventListener('click', (e) => {
    if (e.target === loginModal) {
        loginModal.style.display = 'none';
    }
    if (e.target === registerModal) {
        registerModal.style.display = 'none';
    }
});

// Login Form Submission (modified)
document.getElementById('login-form').addEventListener('submit', function(e) {
    // Let the form submit normally to login.php
    // The PHP will handle the response/redirect
    loginModal.style.display = 'none';
});



// Modal Switching Logic
document.addEventListener("DOMContentLoaded", function() {
    const loginModal = document.getElementById("login-modal");
    const forgotPasswordModal = document.getElementById("forgot-password-modal");
    const forgotPasswordLink = document.getElementById("forgot-password-link");
    const switchToLogin = document.getElementById("switch-to-login");
    const closeModals = document.querySelectorAll(".close-modal");

    // Open Forgot Password Modal
    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener("click", function(e) {
            e.preventDefault();
            loginModal.style.display = "none";
            if (forgotPasswordModal) {
                forgotPasswordModal.style.display = "flex";
            }
        });
    }

    // Switch Back to Login Modal
    if (switchToLogin) {
        switchToLogin.addEventListener("click", function(e) {
            e.preventDefault();
            if (forgotPasswordModal) {
                forgotPasswordModal.style.display = "none";
            }
            loginModal.style.display = "flex";
        });
    }

    // Close Modals
    closeModals.forEach((closeBtn) => {
        closeBtn.addEventListener("click", function() {
            loginModal.style.display = "none";
            if (forgotPasswordModal) {
                forgotPasswordModal.style.display = "none";
            }
        });
    });

    // Close Modal When Clicking Outside
    window.addEventListener("click", function(e) {
        if (e.target === loginModal || e.target === forgotPasswordModal) {
            loginModal.style.display = "none";
            if (forgotPasswordModal) {
                forgotPasswordModal.style.display = "none";
            }
        }
    });
});

// Rating System Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle star rating selection
    const starRatings = document.querySelectorAll('.star-rating i');
    
    starRatings.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            const starContainer = this.parentElement;
            const ratingText = starContainer.querySelector('.rating-text');
            
            // Update star display
            starContainer.querySelectorAll('i').forEach((s, index) => {
                if (index < rating) {
                    s.classList.remove('far');
                    s.classList.add('fas', 'active');
                } else {
                    s.classList.remove('fas', 'active');
                    s.classList.add('far');
                }
            });
            
            // Update rating text
            const ratingTexts = [
                "Poor",
                "Fair",
                "Good",
                "Very Good",
                "Excellent"
            ];
            ratingText.textContent = ratingTexts[rating - 1];
            
            // Store the rating on the container for later use
            starContainer.setAttribute('data-selected-rating', rating);
        });
    });
    
    // Handle rating confirmation
    const confirmButtons = document.querySelectorAll('.confirm-rating');
    
    confirmButtons.forEach(button => {
        button.addEventListener('click', function() {
            const ratingSection = this.closest('.rating-section');
            const starContainer = ratingSection.querySelector('.star-rating');
            const commentInput = ratingSection.querySelector('.rating-comment-input');
            const selectedRating = starContainer.getAttribute('data-selected-rating');
            
            if (!selectedRating) {
                alert('Please select a rating before confirming');
                return;
            }
            
            // Create the confirmed rating display
            const ratingDisplay = document.createElement('div');
            ratingDisplay.className = 'rating-display';
            
            // Add stars
            const starsDiv = document.createElement('div');
            starsDiv.className = 'stars';
            for (let i = 0; i < 5; i++) {
                const star = document.createElement('i');
                star.className = i < selectedRating ? 'fas fa-star' : 'far fa-star';
                starsDiv.appendChild(star);
            }
            ratingDisplay.appendChild(starsDiv);
            
            // Add comment if exists
            if (commentInput && commentInput.value) {
                const commentP = document.createElement('p');
                commentP.className = 'rating-comment';
                commentP.textContent = `"${commentInput.value}"`;
                ratingDisplay.appendChild(commentP);
            }
            
            // Add confirmed notice
            const noticeP = document.createElement('p');
            noticeP.className = 'rating-notice';
            noticeP.innerHTML = '<i class="fas fa-lock"></i> Rating confirmed';
            
            // Replace the rating input with the display
            ratingSection.innerHTML = '';
            ratingSection.classList.add('confirmed');
            ratingSection.appendChild(ratingDisplay);
            ratingSection.appendChild(noticeP);
            
            // Here you would typically send the rating to server
            console.log('Rating submitted:', {
                rating: selectedRating,
                comment: commentInput ? commentInput.value : null
            });
        });
    });
});