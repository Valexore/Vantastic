
document.addEventListener('DOMContentLoaded', function() {
    // Order Ticket Form Submission
    const ticketForm = document.getElementById('ticketForm');
    const orderConfirmation = document.getElementById('order-confirmation');
    
    if (ticketForm) {
      ticketForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form values
        const terminal = document.getElementById('terminal').value;
        const destination = document.getElementById('destination').value;
        const date = document.getElementById('date').value;
        
        // Format the date for display
        const formattedDate = new Date(date).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
        
        // Generate a random ticket number
        const ticketNumber = 'VT' + new Date().getFullYear() + '-' + 
          Math.floor(1000 + Math.random() * 9000);
        
        // Update the popup content
        document.getElementById('popup-ticket-number').textContent = ticketNumber;
        document.getElementById('popup-terminal').textContent = 
          terminal === 'manila' ? 'Manila Terminal' : 'Quezon City Terminal';
        document.getElementById('popup-destination').textContent = 
          destination === 'quezon_city' ? 'Quezon City' : 'Sta. Mesa';
        document.getElementById('popup-date').textContent = formattedDate;
        
        // Show the popup
        orderConfirmation.classList.add('active');
        
        // Simulate van status changes (for demo purposes)
        const statusBox = document.getElementById('status-box');
        statusBox.className = 'status-box on-the-way';
        statusBox.querySelector('.status-message').textContent = 'Your van is on the way';
        statusBox.querySelector('.status-update').textContent = 'Estimated arrival: 15 minutes';
        
        // After 5 seconds, change status to "arrived" (demo only)
        setTimeout(() => {
          statusBox.className = 'status-box arrived';
          statusBox.querySelector('.status-message').textContent = 'Your van has arrived';
          statusBox.querySelector('.status-update').textContent = 'Please proceed to boarding area';
        }, 5000);
      });
    }
    
    // Close popup
    const closePopup = document.querySelector('.close-popup');
    if (closePopup) {
      closePopup.addEventListener('click', function() {
        orderConfirmation.classList.remove('active');
      });
    }
    
    // Close popup when clicking outside
    orderConfirmation.addEventListener('click', function(e) {
      if (e.target === orderConfirmation) {
        orderConfirmation.classList.remove('active');
      }
    });
  });
  
  //--------------------------------------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------------------------
    //--------------------------------------------------------------------------------------------------------------
  