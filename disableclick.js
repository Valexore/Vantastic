







// ░▒▓██████▓▒░░▒▓███████▓▒░▒▓████████▓▒░▒▓█▓▒░             ░▒▓██████▓▒░ ░▒▓██████▓▒░░▒▓███████▓▒░░▒▓█▓▒░░▒▓█▓▒░ 
//░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░   ░▒▓█▓▒░            ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ 
//░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░   ░▒▓█▓▒░            ░▒▓█▓▒░      ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ 
//░▒▓████████▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░   ░▒▓█▓▒░            ░▒▓█▓▒░      ░▒▓█▓▒░░▒▓█▓▒░▒▓███████▓▒░ ░▒▓██████▓▒░  
//░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░   ░▒▓█▓▒░            ░▒▓█▓▒░      ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░         ░▒▓█▓▒░     
//░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░   ░▒▓█▓▒░            ░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░         ░▒▓█▓▒░     
//░▒▓█▓▒░░▒▓█▓▒░▒▓█▓▒░░▒▓█▓▒░ ░▒▓█▓▒░   ░▒▓█▓▒░             ░▒▓██████▓▒░ ░▒▓██████▓▒░░▒▓█▓▒░         ░▒▓█▓▒░     
                                                                                                               


//
//░░░░░░░░░░░░░░░▄▄░░░░░░░░░░░
//░░░░░░░░░░░░░░█░░█░░░░░░░░░░
//░░░░░░░░░░░░░░█░░█░░░░░░░░░░
//░░░░░░░░░░░░░░█░░█░░░░░░░░░░
//░░░░░░░░░░░░░░█░░█░░░░░░░░░░
//██████▄███▄████░░███▄░░░░░░░         ---->         AVOID PLAGIARISM MFS
//▓▓▓▓▓▓█░░░█░░░█░░█░░░███░░░░
//▓▓▓▓▓▓█░░░█░░░█░░█░░░█░░█░░░
//▓▓▓▓▓▓█░░░░░░░░░░░░░░█░░█░░░
//▓▓▓▓▓▓█░░░░░░░░░░░░░░░░█░░░░
//▓▓▓▓▓▓█░░░░░░░░░░░░░░██░░░░░
//▓▓▓▓▓▓█████░░░░░░░░░██░░░░░
//█████▀░░░░▀▀████████░░░░░░

                                                                                                               
























































































// Prevent right-click menu
document.addEventListener('contextmenu', function(e) {
  e.preventDefault();
});

// Prevent refresh via right-click
document.addEventListener('mousedown', function(e) {
  if (e.button === 2) { // Right click
    e.preventDefault();
  }
});

// Disable all refresh methods
document.addEventListener('keydown', function(e) {
  // Block F5, Ctrl+R, Ctrl+Shift+R, Ctrl+F5
  if ((e.key === 'F5') || 
      (e.key === 'Refresh') ||
      (e.ctrlKey && e.key === 'r') || 
      (e.ctrlKey && e.key === 'R') ||
      (e.ctrlKey && e.shiftKey && e.key === 'R')) {
    e.preventDefault();
    e.stopPropagation();
  }
  
  // Block F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
  if (e.key === 'F12' || 
      (e.ctrlKey && e.shiftKey && e.key === 'I') || 
      (e.ctrlKey && e.shiftKey && e.key === 'J') ||
      (e.ctrlKey && e.key === 'U') ||
      (e.metaKey && e.altKey && e.key === 'I') ||
      (e.ctrlKey && e.shiftKey && e.key === 'C')) {
    e.preventDefault();
    e.stopPropagation();
  }
});

// Extra protection against developer tools opening
let devtools = /./;
devtools.toString = function() {
  this.opened = true;
  return '';
};

setInterval(function() {
  if (devtools.opened) {
    devtools.opened = false;
    window.location.reload();
    // Or you can redirect to another page
    // window.location.href = "about:blank";
  }
}, 1000);

// Prevent drag-and-drop to new tab which could be used to inspect
document.addEventListener('dragstart', function(e) {
  e.preventDefault();
});

// Additional protection against iframe busting
if (window.self !== window.top) {
  window.top.location = window.self.location;
}

// Disable text selection (optional)
document.addEventListener('selectstart', function(e) {
  e.preventDefault();
});



  // Prevent right-click menu
document.addEventListener('contextmenu', function(e) {
  e.preventDefault();
  return false;
});

// Prevent refresh via right-click
document.addEventListener('mousedown', function(e) {
  if (e.button === 2) { // Right click
    e.preventDefault();
  }
});

// Disable all refresh methods
document.addEventListener('keydown', function(e) {
  // Block F5, Ctrl+R, Ctrl+Shift+R, Ctrl+F5
  if ((e.key === 'F5') || 
      (e.key === 'Refresh') ||
      (e.ctrlKey && e.key === 'r') || 
      (e.ctrlKey && e.key === 'R') ||
      (e.ctrlKey && e.shiftKey && e.key === 'R')) {
    e.preventDefault();
    e.stopPropagation();
  }
  
  // Block F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
  if (e.key === 'F12' || 
      (e.ctrlKey && e.shiftKey && e.key === 'I') || 
      (e.ctrlKey && e.shiftKey && e.key === 'J') ||
      (e.ctrlKey && e.key === 'U') ||
      (e.metaKey && e.altKey && e.key === 'I') ||
      (e.ctrlKey && e.shiftKey && e.key === 'C')) {
    e.preventDefault();
    e.stopPropagation();
  }
});

// Extra protection against developer tools opening
let devtools = /./;
devtools.toString = function() {
  this.opened = true;
  return '';
};

setInterval(function() {
  if (devtools.opened) {
    devtools.opened = false;
    window.location.reload();
    // Or you can redirect to another page
    // window.location.href = "about:blank";
  }
}, 1000);

// Prevent drag-and-drop to new tab which could be used to inspect
document.addEventListener('dragstart', function(e) {
  e.preventDefault();
});

// Additional protection against iframe busting
if (window.self !== window.top) {
  window.top.location = window.self.location;
}

// Disable text selection (optional)
document.addEventListener('selectstart', function(e) {
  e.preventDefault();
});


// 1. Prevent right-click completely
document.addEventListener('contextmenu', function(e) {
  e.preventDefault();
  return false;
});

// 2. Prevent any mouse button inspection tricks
document.addEventListener('mousedown', function(e) {
  if (e.button === 2 || e.button === 3) { // Right click and middle click
    e.preventDefault();
    return false;
  }
});

// 3. Nuclear option - break the page if devtools is detected
(function() {
  function devtoolsOpen() {
    document.body.innerHTML = '<h1>Developer Tools Detected</h1>';
    window.location.href = 'about:blank';
    setInterval(function() {
      document.body.innerHTML += '<br>Please close developer tools';
    }, 1000);
  }
  
  // Check for devtools on load
  let devtools = /./;
  devtools.toString = function() {
    devtoolsOpen();
    return '';
  };
  
  console.log('%c', devtools);
  
  // Check periodically
  setInterval(function() {
    if (window.outerWidth - window.innerWidth > 100 || 
        window.outerHeight - window.innerHeight > 100) {
      devtoolsOpen();
    }
  }, 500);
})();

// 4. Disable all refresh methods
document.addEventListener('keydown', function(e) {
  // Block F5, Ctrl+R, Ctrl+Shift+R, Ctrl+F5
  if (e.key === 'F5' || 
      e.key === 'Refresh' ||
      (e.ctrlKey && e.key === 'r') || 
      (e.ctrlKey && e.key === 'R') ||
      (e.ctrlKey && e.shiftKey && e.key === 'R') ||
      (e.key === 'F12') || 
      (e.ctrlKey && e.shiftKey && e.key === 'I') || 
      (e.ctrlKey && e.shiftKey && e.key === 'J') ||
      (e.ctrlKey && e.key === 'U') ||
      (e.metaKey && e.altKey && e.key === 'I') ||
      (e.ctrlKey && e.shiftKey && e.key === 'C')) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();
    return false;
  }
});

// 5. Prevent iframe busting
if (window.self !== window.top) {
  window.top.location = window.self.location;
}

// 6. Extra protection - disable keyboard completely if needed
document.addEventListener('keydown', function(e) {
  if (e.ctrlKey || e.metaKey || e.altKey) {
    e.preventDefault();
    return false;
  }
});

// 7. Clear console periodically to prevent debugging
setInterval(function() {
  console.clear();
}, 1000);

// 8. Disable text selection
document.addEventListener('selectstart', function(e) {
  e.preventDefault();
  return false;
});

// 9. Disable drag-and-drop
document.addEventListener('dragstart', function(e) {
  e.preventDefault();
  return false;
});
