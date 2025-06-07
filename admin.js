document.addEventListener('DOMContentLoaded', function() {
    // Sidebar navigation
    const menuLinks = document.querySelectorAll('.sidebar-menu a');
    const sections = document.querySelectorAll('.dashboard-section');
    
    menuLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all links and sections
        menuLinks.forEach(l => l.classList.remove('active'));
        sections.forEach(s => s.classList.remove('active'));
        
        // Add active class to clicked link
        this.classList.add('active');
        
        // Show corresponding section
        const sectionId = this.getAttribute('data-section');
        document.getElementById(sectionId).classList.add('active');
      });
    });
    
    // Logout button
    document.getElementById('logout-btn').addEventListener('click', function() {
      // Add logout functionality here
      window.location.href = 'login.html';
    });
    
    // Settings tabs
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
      button.addEventListener('click', function() {
        const tabId = this.getAttribute('data-tab');
        
        // Remove active class from all buttons and contents
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));
        
        // Add active class to clicked button and corresponding content
        this.classList.add('active');
        document.getElementById(tabId).classList.add('active');
      });
    });
    
    // Custom date range toggle
    const reportPeriod = document.getElementById('report-period');
    const customDateRange = document.getElementById('custom-date-range');
    
    reportPeriod.addEventListener('change', function() {
      if (this.value === 'custom') {
        customDateRange.style.display = 'flex';
      } else {
        customDateRange.style.display = 'none';
      }
    });
    
    // Modal functionality
    const modals = document.querySelectorAll('.modal');
    const closeModalButtons = document.querySelectorAll('.close-modal, .cancel-btn');
    
    function openModal(modalId) {
      document.getElementById(modalId).style.display = 'flex';
    }
    
    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
    }
    
    // Add User Modal
    document.getElementById('add-user-btn')?.addEventListener('click', () => openModal('add-user-modal'));
    document.getElementById('cancel-add-user')?.addEventListener('click', () => closeModal('add-user-modal'));
    
    // Edit User Modal
    const editUserButtons = document.querySelectorAll('.edit-user-btn');
    editUserButtons.forEach(button => {
      button.addEventListener('click', function() {
        const userId = this.closest('tr').getAttribute('data-user-id');
        // Here you would fetch user data based on userId
        // For demo purposes, we'll just open the modal
        openModal('edit-user-modal');
      });
    });
    
    document.getElementById('cancel-edit-user')?.addEventListener('click', () => closeModal('edit-user-modal'));
    
    // Delete Confirmation Modal
    const deleteButtons = document.querySelectorAll('.delete-user-btn, .delete-van-btn, .delete-route-btn');
    deleteButtons.forEach(button => {
      button.addEventListener('click', function() {
        const itemType = this.classList.contains('delete-user-btn') ? 'user' : 
                        this.classList.contains('delete-van-btn') ? 'van' : 'route';
        const itemId = this.closest('tr').getAttribute(`data-${itemType}-id`);
        
        document.getElementById('delete-modal-message').textContent = 
          `Are you sure you want to delete this ${itemType} (${itemId})? This action cannot be undone.`;
        
        openModal('delete-modal');
        
        document.getElementById('confirm-delete').onclick = function() {
          // Add delete functionality here
          alert(`${itemType} ${itemId} deleted`);
          closeModal('delete-modal');
        };
      });
    });
    
    document.getElementById('cancel-delete')?.addEventListener('click', () => closeModal('delete-modal'));
    
    // Add Route Modal
    document.getElementById('add-route-btn')?.addEventListener('click', () => openModal('add-route-modal'));
    document.getElementById('cancel-add-route')?.addEventListener('click', () => closeModal('add-route-modal'));
    
    // Close modal when clicking outside
    modals.forEach(modal => {
      modal.addEventListener('click', function(e) {
        if (e.target === this) {
          closeModal(this.id);
        }
      });
    });
    
    // Form submissions
    document.getElementById('add-user-form')?.addEventListener('submit', function(e) {
      e.preventDefault();
      // Add user creation logic here
      alert('User added successfully');
      closeModal('add-user-modal');
    });
    
    document.getElementById('edit-user-form')?.addEventListener('submit', function(e) {
      e.preventDefault();
      // Add user update logic here
      alert('User updated successfully');
      closeModal('edit-user-modal');
    });
    
    document.getElementById('add-route-form')?.addEventListener('submit', function(e) {
      e.preventDefault();
      // Add route creation logic here
      alert('Route added successfully');
      closeModal('add-route-modal');
    });
    
    // Reset password button 
    document.getElementById('reset-password-btn')?.addEventListener('click', function() {
      // Add password reset logic here
      alert('Password reset link sent to user');
    });
    
    // Generate report button
    document.getElementById('generate-report')?.addEventListener('click', function() {
      // Add report generation logic here
      alert('Report generated');
    });
    
    // Refresh data button
    document.getElementById('refresh-data')?.addEventListener('click', function() {
      // Add data refresh logic here
      alert('Data refreshed');
    });
    
    // Reset filters button
    document.getElementById('reset-user-filters')?.addEventListener('click', function() {
      // Reset all user filters
      document.getElementById('user-role-filter').value = 'all';
      document.getElementById('user-status-filter').value = 'all';
      alert('Filters reset');
    });
  });   