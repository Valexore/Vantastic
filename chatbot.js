// Chatbot Logic
const chatbotToggle = document.getElementById('chatbot-toggle');
const chatbotWindow = document.querySelector('.chatbot-window');
const chatbotClose = document.getElementById('chatbot-close');
const chatbotMessages = document.querySelector('.chatbot-messages');
const chatbotInput = document.getElementById('chatbot-input');
const chatbotSend = document.getElementById('chatbot-send');

// Toggle chatbot window
chatbotToggle.addEventListener('click', () => {
  chatbotWindow.style.display = chatbotWindow.style.display === 'block' ? 'none' : 'block';
  if (chatbotWindow.style.display === 'block') {
    chatbotInput.focus();
  }
});

// Close chatbot window
chatbotClose.addEventListener('click', () => {
  chatbotWindow.style.display = 'none';
});

// Chatbot responses and keywords
const chatbotResponses = {
  "book": "You can book a ticket by visiting the 'Seat Booking' section.",
  "ticket": "You can book a ticket by visiting the 'Seat Booking' section.",
  "fare": "Fare details are available in the 'Fare' section.",
  "price": "Fare details are available in the 'Fare' section.",
  "cost": "Fare details are available in the 'Fare' section.",
  "traffic": "Traffic updates are available in the 'Traffic Conditions' section.",
  "miss": "I'm sorry I don't have human emotion to understand that, but maybe..",
  "weather": "Weather updates are available in the 'Weather Update' section.",
  "support": "You can contact support by calling +1 (123) 456-7890 or emailing support@terminal.com.",
  "help": "You can contact support by calling +1 (123) 456-7890 or emailing support@terminal.com.",
  "nalaman": "Ano yan?",
  "kanya": "Sino?",
  "secret": "Okay!! basta don't forget your past.",
  "contact": "You can contact support by calling +1 (123) 456-7890 or emailing support@terminal.com."
};

const defaultResponses = [
  "I'm not sure I understand. Could you rephrase that?",
  "I'm still learning. Could you ask about booking tickets, fares, traffic, or weather?",
  "I can help with ticket booking, fare details, traffic updates, and weather information. What would you like to know?"
];

// Function to add a message to the chat
function addMessage(message, sender) {
  const messageElement = document.createElement('div');
  messageElement.classList.add('message', `${sender}-message`);

  // Add bot avatar for bot messages
  if (sender === 'bot') {
    const avatarElement = document.createElement('div');
    avatarElement.classList.add('avatar');
    avatarElement.innerHTML = `<img src="img/logo.png" alt="Bot Avatar">`;
    messageElement.appendChild(avatarElement);
  }

  const messageContent = document.createElement('div');
  messageContent.classList.add('message-content');
  messageContent.innerHTML = `<p>${message}</p>`;

  messageElement.appendChild(messageContent);
  chatbotMessages.appendChild(messageElement);

  // Scroll to the latest message
  chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

// Function to show loading animation
function showLoading() {
  const loadingElement = document.createElement('div');
  loadingElement.classList.add('message', 'bot-message', 'loading');

  const avatarElement = document.createElement('div');
  avatarElement.classList.add('avatar');
  avatarElement.innerHTML = `<img src="img/logo.png" alt="Bot Avatar">`;
  loadingElement.appendChild(avatarElement);

  const loadingContent = document.createElement('div');
  loadingContent.classList.add('message-content');
  loadingContent.innerHTML = `
    <div class="loading">
      <div class="loading-dot"></div>
      <div class="loading-dot"></div>
      <div class="loading-dot"></div>
    </div>
  `;

  loadingElement.appendChild(loadingContent);
  chatbotMessages.appendChild(loadingElement);

  // Scroll to the latest message
  chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

// Function to generate bot response
function generateResponse(userMessage) {
  const lowerMessage = userMessage.toLowerCase();
  
  // Check for keywords in the user's message
  for (const keyword in chatbotResponses) {
    if (lowerMessage.includes(keyword)) {
      return chatbotResponses[keyword];
    }
  }
  
  // If no keywords found, return a random default response
  return defaultResponses[Math.floor(Math.random() * defaultResponses.length)];
}

// Function to handle user input
function handleUserInput() {
  const userMessage = chatbotInput.value.trim();
  if (userMessage) {
    addMessage(userMessage, 'user'); // Display user's message
    chatbotInput.value = ''; // Clear input field

    // Show loading animation
    showLoading();

    // Simulate bot typing delay
    setTimeout(() => {
      // Remove loading animation
      const loadingElement = document.querySelector('.loading');
      if (loadingElement) {
        loadingElement.remove();
      }

      // Generate and display bot's response
      const botResponse = generateResponse(userMessage);
      addMessage(botResponse, 'bot');
    }, 1500); // Simulate a 1.5-second delay
  }
}

// Send message on button click
chatbotSend.addEventListener('click', handleUserInput);

// Send message on Enter key
chatbotInput.addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    handleUserInput();
  }
});