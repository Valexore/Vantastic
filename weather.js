// Get current time in Philippines (UTC+8)
function getCurrentTimeInPhilippines() {
  const options = {
      timeZone: 'Asia/Manila',
      hour12: true,
      hour: 'numeric',
      minute: '2-digit'
  };
  return new Date().toLocaleTimeString('en-US', options);
}
// Format time with AM/PM
function formatTime(dateString) {
  return dateString; // Already formatted by toLocaleTimeString
}


// Update all traffic elements
function updateTraffic() {
  const hour = new Date().getHours();
  const manilaToInfantaCard = document.querySelector('.traffic-card.moderate');
  const infantaToManilaCard = document.querySelector('.traffic-card.light');
  
  if (!manilaToInfantaCard || !infantaToManilaCard) return;

  const manilaToInfantaCondition = manilaToInfantaCard.querySelector('.condition');
  const manilaToInfantaDelay = manilaToInfantaCard.querySelector('.delay');
  const manilaToInfantaLight = manilaToInfantaCard.querySelector('.traffic-light');
  const infantaToManilaCondition = infantaToManilaCard.querySelector('.condition');
  const infantaToManilaDelay = infantaToManilaCard.querySelector('.delay');
  const infantaToManilaLight = infantaToManilaCard.querySelector('.traffic-light');

  // Morning rush hour (7am-9am)
  if (hour >= 7 && hour < 9) {
      // Manila to Infanta
      manilaToInfantaCard.className = 'traffic-card heavy';
      manilaToInfantaCondition.textContent = 'Heavy Traffic';
      manilaToInfantaDelay.textContent = '+30 min delay';
      manilaToInfantaLight.style.backgroundColor = '#e74c3c';
      
      // Infanta to Manila
      infantaToManilaCard.className = 'traffic-card moderate';
      infantaToManilaCondition.textContent = 'Moderate Traffic';
      infantaToManilaDelay.textContent = '+20 min delay';
      infantaToManilaLight.style.backgroundColor = '#f39c12';
  } 
  // Late morning (9am-12pm)
  else if (hour >= 9 && hour < 12) {
      // Manila to Infanta
      manilaToInfantaCard.className = 'traffic-card moderate';
      manilaToInfantaCondition.textContent = 'Moderate Traffic';
      manilaToInfantaDelay.textContent = '+15 min delay';
      manilaToInfantaLight.style.backgroundColor = '#f39c12';
      
      // Infanta to Manila
      infantaToManilaCard.className = 'traffic-card light';
      infantaToManilaCondition.textContent = 'Light Traffic';
      infantaToManilaDelay.textContent = '+10 min delay';
      infantaToManilaLight.style.backgroundColor = '#2ecc71';
  } 
  // Lunch time (12pm-2pm)
  else if (hour >= 12 && hour < 14) {
      // Manila to Infanta
      manilaToInfantaCard.className = 'traffic-card light';
      manilaToInfantaCondition.textContent = 'Light Traffic';
      manilaToInfantaDelay.textContent = '+5 min delay';
      manilaToInfantaLight.style.backgroundColor = '#2ecc71';
      
      // Infanta to Manila
      infantaToManilaCard.className = 'traffic-card moderate';
      infantaToManilaCondition.textContent = 'Moderate Traffic';
      infantaToManilaDelay.textContent = '+15 min delay';
      infantaToManilaLight.style.backgroundColor = '#f39c12';
  } 
  // Afternoon (2pm-5pm)
  else if (hour >= 14 && hour < 17) {
      // Manila to Infanta
      manilaToInfantaCard.className = 'traffic-card moderate';
      manilaToInfantaCondition.textContent = 'Moderate Traffic';
      manilaToInfantaDelay.textContent = '+20 min delay';
      manilaToInfantaLight.style.backgroundColor = '#f39c12';
      
      // Infanta to Manila
      infantaToManilaCard.className = 'traffic-card heavy';
      infantaToManilaCondition.textContent = 'Heavy Traffic';
      infantaToManilaDelay.textContent = '+30 min delay';
      infantaToManilaLight.style.backgroundColor = '#e74c3c';
  } 
  // Evening rush hour (5pm-8pm)
  else if (hour >= 17 && hour < 20) {
      // Manila to Infanta
      manilaToInfantaCard.className = 'traffic-card heavy';
      manilaToInfantaCondition.textContent = 'Heavy Traffic';
      manilaToInfantaDelay.textContent = '+40 min delay';
      manilaToInfantaLight.style.backgroundColor = '#e74c3c';
      
      // Infanta to Manila
      infantaToManilaCard.className = 'traffic-card heavy';
      infantaToManilaCondition.textContent = 'Heavy Traffic';
      infantaToManilaDelay.textContent = '+40 min delay';
      infantaToManilaLight.style.backgroundColor = '#e74c3c';
  } 
  // Night time (8pm-7am)
  else {
      // Manila to Infanta
      manilaToInfantaCard.className = 'traffic-card light';
      manilaToInfantaCondition.textContent = 'Light Traffic';
      manilaToInfantaDelay.textContent = 'No delays';
      manilaToInfantaLight.style.backgroundColor = '#2ecc71';
      
      // Infanta to Manila
      infantaToManilaCard.className = 'traffic-card light';
      infantaToManilaCondition.textContent = 'Light Traffic';
      infantaToManilaDelay.textContent = 'No delays';
      infantaToManilaLight.style.backgroundColor = '#2ecc71';
  }
}

// Update last updated time
function updateLastUpdated() {
    const options = {
        timeZone: 'Asia/Manila',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    };
    const now = new Date().toLocaleString('en-US', options);
    document.getElementById('update-time').textContent = now;
}
// Update all sections
function updateSections() {
  const timeElement = document.getElementById('philippines-time');
  if (timeElement) {
      timeElement.textContent = getCurrentTimeInPhilippines();
  }
  updateWeather();
  updateTraffic();
  updateLastUpdated();
}

// Initialize and update every second
updateSections();
setInterval(updateSections, 1000);

// Add refresh button functionality
document.querySelector('.refresh-btn')?.addEventListener('click', function() {
  updateSections();
  this.innerHTML = '<i class="fas fa-sync-alt"></i> Refreshing...';
  setTimeout(() => {
      this.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
  }, 1000);
});