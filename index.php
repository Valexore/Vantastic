<?php
session_start();
include 'config.php';
$response = ['success' => false, 'message' => ''];

// Handle OTP verification if it's being submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['otp'])) {
    $email = trim($_POST['email']);
    $otp = trim($_POST['otp']);
    
    // Validate OTP (6 digits)
    if (strlen($otp) !== 6 || !ctype_digit($otp)) {
        $response['message'] = 'Invalid OTP format';
        echo json_encode($response);
        exit;
    }
    
    // Check if OTP matches and is not expired
    $current_time = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("SELECT id, verification_token FROM users WHERE email = ? AND verification_token_expiry > ?");
    $stmt->bind_param("ss", $email, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if ($user['verification_token'] === $otp) {
            // OTP is valid - mark user as verified
            $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, verification_token_expiry = NULL WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            
            if ($update_stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Verification successful! Your account is now active.';
                $_SESSION['verified_email'] = $email; // Store verified email in session
            } else {
                $response['message'] = 'Error updating verification status';
            }
        } else {
            $response['message'] = 'Invalid OTP code';
        }
    } else {
        $response['message'] = 'OTP expired or invalid. Please request a new one.';
    }
    
    // Return JSON response that will be handled by JavaScript to show alerts
    echo json_encode($response);
    exit;
}
?>

  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/knorr.png" type="image/png">
    <title>Van Terminal System</title>
    <link rel="stylesheet" href="route.css">

    <link rel="stylesheet" href="scrollStyle.css">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  </head>

  
  <style>
    /* Success Modal Animation */
    /* Error Modal Animation */
    .error-animation {
      margin: 0 auto;
      width: 80px;
      height: 80px;
    }

    .crossmark {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: block;
      stroke-width: 5;
      stroke: #f44336;
      stroke-miterlimit: 10;
      box-shadow: 0 0 0 rgba(244, 67, 54, 0.4);
      animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
    }

    .crossmark__circle {
      stroke-dasharray: 166;
      stroke-dashoffset: 166;
      stroke-width: 5;
      stroke-miterlimit: 10;
      stroke: #f44336;
      fill: none;
      animation: stroke .6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }

    .crossmark__cross {
      transform-origin: 50% 50%;
      stroke-dasharray: 48;
      stroke-dashoffset: 48;
      animation: stroke .3s cubic-bezier(0.65, 0, 0.45, 1) .8s forwards;
    }

    .success-animation {
      margin: 0 auto;
      width: 80px;
      height: 80px;
    }

    .checkmark {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      display: block;
      stroke-width: 5;
      stroke: #4CAF50;
      stroke-miterlimit: 10;
      box-shadow: 0 0 0 rgba(76, 175, 80, 0.4);
      animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
    }

    .checkmark__circle {
      stroke-dasharray: 166;
      stroke-dashoffset: 166;
      stroke-width: 5;
      stroke-miterlimit: 10;
      stroke: #4CAF50;
      fill: none;
      animation: stroke .6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }

    .checkmark__check {
      transform-origin: 50% 50%;
      stroke-dasharray: 48;
      stroke-dashoffset: 48;
      animation: stroke .3s cubic-bezier(0.65, 0, 0.45, 1) .8s forwards;
    }

    /* Loading animation */
    .loader {
      width: 100%;
      height: 4px;
      background: #f3f3f3;
      border-radius: 2px;
      margin: 20px auto;
      position: relative;
      overflow: hidden;
    }

    .loader:before {
      content: '';
      position: absolute;
      left: -50%;
      height: 100%;
      width: 40%;
      background-color: #4CAF50;
      animation: loading 2s linear infinite;
    }

    @keyframes loading {
      0% {
        left: -40%;
      }

      50% {
        left: 20%;
        width: 80%;
      }

      100% {
        left: 100%;
        width: 100%;
      }
    }

    @keyframes stroke {
      100% {
        stroke-dashoffset: 0;
      }
    }

    @keyframes scale {

      0%,
      100% {
        transform: none;
      }

      50% {
        transform: scale3d(1.1, 1.1, 1);
      }
    }

    @keyframes fill {
      100% {
        box-shadow: inset 0 0 0 100px rgba(76, 175, 80, 0);
      }
    }
  </style>

  <body>
    <!-- Hero Section -->
    <section class="hero" id="hero">
      <!-- Video background element -->
      <video autoplay muted loop playsinline class="hero-video">
        <source src="img/vidTara.mp4" type="video/mp4">
        <!-- Fallback image if video doesn't load -->
        <img src="img/404.png" alt="Background">
      </video>
      <div class="hero-content">
        <h2>San ka punta?</h2>
        <div class="changing-text"></div>
        <div class="line"></div>
        <p>"Miss mo na ba? I-travel mo na lang yan!"</p>

        <!-- <p>"Sit back, relax and let your journey begins!"</p> -->
      </div>
    </section>



    <header>
      <!-- Left empty div for balance -->
      <div class="header-left"></div>

      <!-- Centered Logo -->
      <div class="logo">
        <h1>
          <img src="img/VanTastic.png" alt="Van Terminal System" class="logo-top">
          <img src="img/VanTastic.png" alt="Van Terminal System" class="logo-scrolled">
        </h1>
      </div>

      <!-- Right side with menu and login -->
      <div class="header-right">
        <!-- Login/Register Button -->
        <div class="menu-container">
          <a href="#" class="login-register-btn"><i class="fa fa-user" ></i></a>
          <nav id="nav-menu2" class="glass-effect">
            <div class="menu-header2">
              <span class="menu-title2">Account</span>
            </div>
            <ul>
              <li>
                <a href="#" class="login-link">
                  <i class="fas fa-sign-in-alt"></i>
                  <span class="link-text" data-modal="login-modal">Login</span>
                </a>
              </li>
              <li>
                <a href="#" class="register-link">
                  <i class="fas fa-user-plus"></i>
                  <span class="link-text" data-modal="register-modal">Register</span>
                </a>
              </li>
            </ul>
          </nav>
          <div class="menu-toggle">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
          </div>
        </div>
      </div>

      <!-- Navigation Menu -->
      <nav id="nav-menu" class="glass-effect">
        <div class="menu-header">
          <span class="menu-title">Navigation</span>
        </div>
        <ul>
          <li><a href="#hero"><i class="fas fa-home"></i><span class="link-text">Home</span></a></li>
          <li><a href="#fare"><i class="fas fa-tag"></i><span class="link-text">Fare</span></a></li>
          <li><a href="#about"><i class="fas fa-info-circle"></i><span class="link-text">About Us</span></a></li>
          <li><a href="#routesSection"><i class="fas fa-route"></i><span class="link-text">Routes</span></a></li>
          <li><a href="#organization"><i class="fas fa-sitemap"></i><span class="link-text">Organization</span></a></li>
          <li><a href="#contact"><i class="fas fa-envelope"></i><span class="link-text">Contact</span></a></li>
        </ul>
      </nav>
    </header>
   
    <div class="slider no-snap" reverse="true" style="
      --width: 250px;
      --height: 50px;
      --quantity: 10;
      ">
      <!-- Slide -->
      <div class="slider" reverse="true" style="
      --width: 250px;
      --height: 50px;
      --quantity: 10;
      ">
        <div class="list">
          <div class="item" style="--position: 1"><img src="img/safe.png" alt=""></div>
          <div class="item" style="--position: 2"><img src="img/caution.png" alt=""></div>
          <div class="item" style="--position: 3"><img src="img/caution.png" alt=""></div>
          <div class="item" style="--position: 4"><img src="img/caution.png" alt=""></div>
          <div class="item" style="--position: 5"><img src="img/caution.png" alt=""></div>
          <div class="item" style="--position: 6"><img src="img/caution.png" alt=""></div>
          <div class="item" style="--position: 7"><img src="img/caution.png" alt=""></div>
          <div class="item" style="--position: 8"><img src="img/caution.png" alt=""></div>
          <div class="item" style="--position: 9"><img src="img/caution.png" alt=""></div>
          <div class="item" style="--position: 10"><img src="img/caution.png" alt=""></div>
        </div>
      </div>
    </div>



<form id="otpForm" method="post">
    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
    <div>
        <label for="otp">Enter OTP:</label>
        <input type="text" id="otp" name="otp" required maxlength="6">
    </div>
    <button type="submit">Verify OTP</button>
</form>

<div id="message"></div>




    <section id="fare" class="section" data-animate="animate-fadeIn">
      <div class="section-header">
        <img src="svgs/bokfont.svg" alt="Booking & Fare Options" class="svgfonti">
      </div>

      <div class="fare-cards">
        <div class="fare-card">
          <div class="fare-card-header">
            <h3>Regular Fare</h3>
            <div class="fare-price">₱350</div>
          </div>
          <div class="fare-card-body">
            <ul class="fare-features">
              <li>Standard pricing for all passengers</li>
              <li>No eligibility requirements</li>
              <li>Applicable to all routes</li>
            </ul>
          </div>
        </div>

        <div class="fare-card featured">
          <div class="fare-card-header">
            <h3>Discounted Fare</h3>
            <div class="fare-price">₱320</div>
          </div>
          <div class="fare-card-body">
            <ul class="fare-features">
              <li>Available for students with valid ID</li>
              <li>For senior citizens (60+ years)</li>
              <li>Persons with Disabilities (PWD)</li>
            </ul>
          </div>
          <div class="fare-card-badge">Best Value</div>
        </div>
      </div>

      <div class="fare-notice">
        <p>Please present valid identification to avail of discounted fares. Discounts cannot be combined with other promotions.</p>
      </div>
      <br>
      <div class="options">
        <div class="option" data-modal="login-modal">
          <h3>Book Now!</h3>
        </div>
        <div class="option" data-modal="register-modal">
          <h3>Be one of us!</h3>
        </div>
      </div>
    </section>





    
<br><br>
<div class="about-wave"></div>




<!-- about ius -->
<section id="about" class="section" data-animate="animate-fadeIn">
  <div class="section-header">
  <img src="svgs/aboutes.svg" alt="About VanTastic" class="svgfonti">
   </div>

  <div class="about-container">
    <div class="about-content">
      <div class="about-text">
        <div class="about-card">
          <h3>Who We Are</h3>
          <p>VanTastic, operated by REINNAVVODAI, delivers safe, reliable, and convenient transportation with modern technology and exceptional service, ensuring hassle-free travel for all.</p>
        </div>
        
        <div class="about-card">
          <h3>Our Mission</h3>
          <p>To deliver safe, reliable, and affordable transportation with well-maintained vans and professional drivers, connecting communities through exceptional service for all travel needs.</p>
        </div>
        
        <div class="about-card values-card">
          <h3>Our Values</h3>
          <ul class="values-list">
  <li>
    <div class="icon-container" style="background-color: rgba(0, 180, 180, 0.1);">
      <i class="fas fa-shield-alt" style="color: var(--teal);"></i>
    </div>
    <div class="value-text">
      <strong style="color: var(--teal);">Safety First:</strong> Your security is our top priority
    </div>
  </li>
  <li>
    <div class="icon-container" style="background-color: rgba(0, 180, 180, 0.1);">
      <i class="fas fa-clock" style="color: var(--teal);"></i>
    </div>
    <div class="value-text">
      <strong style="color: var(--teal);">Punctuality:</strong> We value your time as much as you do
    </div>
  </li>
  <li>
    <div class="icon-container" style="background-color: rgba(0, 180, 180, 0.1);">
      <i class="fas fa-heart" style="color: var(--teal);"></i>
    </div>
    <div class="value-text">
      <strong style="color: var(--teal);">Comfort:</strong> Travel in our well-maintained, air-conditioned vans
    </div>
  </li>
  <li>
    <div class="icon-container" style="background-color: rgba(0, 180, 180, 0.1);">
      <i class="fas fa-hand-holding-heart" style="color: var(--teal);"></i>
    </div>
    <div class="value-text">
      <strong style="color: var(--teal);">Service:</strong> Friendly and professional drivers and staff
    </div>
  </li>
</ul>
        </div>
      </div>
      
      <div class="about-visual" >
        <div class="van-svg-container">
          <img src="svgs/vantis.svg" alt="iror" class="svgban">
        </div>
        
        <div class="stats-container">
          <div class="stat-item">
            <div class="stat-number" data-count="4">0</div>
            <div class="stat-label">Years in Service</div>
          </div>
          <div class="stat-item">
            <div class="stat-number" data-count="34">0</div>
            <div class="stat-label">Vans in Fleet</div>
          </div>
          <div class="stat-item">
            <div class="stat-number" data-count="2947">0</div>
            <div class="stat-label">Happy Passengers</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>




    <section id="routesSection" class="section routes-section-js">
      <div class="section-header-js">
      <img src="svgs/destifont.svg" alt="Discover Our Destinations" class="svgfonti">
      </div>

      <div class="routes-carousel-container-js">
        <button class="carousel-nav-js prev-js" aria-label="Previous destination">
          <i class="fas fa-chevron-left"></i>
        </button>

        <div class="routes-carousel-js">
          <!-- Real, Quezon Province -->
          <div class="route-card-js" data-destination="Real, Quezon Province" data-fare="₱350" data-stop="Infanta"
            data-highlights='["Balagbag Falls","Mount Banahaw","Kanaway Beach"]'
            data-icons='["water","mountain","umbrella-beach"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Real</h3>
                <span class="province-js">Quezon Province</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>
          <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script>
          <!-- Sta. Maria, Laguna -->
          <div class="route-card-js" data-destination="Sta. Maria, Laguna" data-fare="₱350" data-stop="Famy"
            data-highlights='["Masungi Georeserve","Sapinit Falls","Tanaw Restaurant"]'
            data-icons='["mountain-city","horse","burger-sooda"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Sta. Maria</h3>
                <span class="province-js">Laguna</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Baras, Rizal -->
          <div class="route-card-js" data-destination="Baras, Rizal" data-fare="₱350" data-stop="Tanay"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Baras</h3>
                <span class="province-js">Rizal</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Pinugay, Antipolo -->
          <div class="route-card-js" data-destination="Pinugay, Antipolo" data-fare="₱350" data-stop="Antipolo"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Pinugay</h3>
                <span class="province-js">Antipolo</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Boso-Boso, Antipolo -->
          <div class="route-card-js" data-destination="Boso-Boso, Antipolo" data-fare="₱350" data-stop="Antipolo"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Boso-Boso</h3>
                <span class="province-js">Antipolo</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Padilla, Antipolo -->
          <div class="route-card-js" data-destination="Padilla, Antipolo" data-fare="₱350" data-stop="Antipolo"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Padilla</h3>
                <span class="province-js">Antipolo</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Cogeo Avenue, Antipolo -->
          <div class="route-card-js" data-destination="Cogeo Avenue, Antipolo" data-fare="₱350" data-stop="Antipolo"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Cogeo Avenue</h3>
                <span class="province-js">Antipolo</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Marikina -->
          <div class="route-card-js" data-destination="Marikina" data-fare="₱350" data-stop="Cubao"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Marikina</h3>
                <span class="province-js">Metro Manila</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Rizal -->
          <div class="route-card-js" data-destination="Rizal" data-fare="₱350" data-stop="Antipolo"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Rizal</h3>
                <span class="province-js">Province</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Quezon City -->
          <div class="route-card-js" data-destination="Quezon City" data-fare="₱350" data-stop="Cubao"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Quezon City</h3>
                <span class="province-js">Metro Manila</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Sta. Mesa, Manila -->
          <div class="route-card-js" data-destination="Sta. Mesa, Manila" data-fare="₱350" data-stop="Legarda"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Sta. Mesa</h3>
                <span class="province-js">Manila</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Famy, Laguna -->
          <div class="route-card-js" data-destination="Famy, Laguna" data-fare="₱350" data-stop="Siniloan"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Famy</h3>
                <span class="province-js">Laguna</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Mabitac, Laguna -->
          <div class="route-card-js" data-destination="Mabitac, Laguna" data-fare="₱350" data-stop="Famy"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Mabitac</h3>
                <span class="province-js">Laguna</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Pililla, Rizal -->
          <div class="route-card-js" data-destination="Pililla, Rizal" data-fare="₱350" data-stop="Tanay"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Pililla</h3>
                <span class="province-js">Rizal</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Tanay, Rizal -->
          <div class="route-card-js" data-destination="Tanay, Rizal" data-fare="₱350" data-stop="Antipolo"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Tanay</h3>
                <span class="province-js">Rizal</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Morong, Rizal -->
          <div class="route-card-js" data-destination="Morong, Rizal" data-fare="₱350" data-stop="Baras"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Morong</h3>
                <span class="province-js">Rizal</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Teresa, Rizal -->
          <div class="route-card-js" data-destination="Teresa, Rizal" data-fare="₱350" data-stop="Antipolo"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Teresa</h3>
                <span class="province-js">Rizal</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Antipolo, Rizal -->
          <div class="route-card-js" data-destination="Antipolo, Rizal" data-fare="₱350" data-stop="Cogeo"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Antipolo</h3>
                <span class="province-js">Rizal</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Masinag, Antipolo -->
          <div class="route-card-js" data-destination="Masinag, Antipolo" data-fare="₱350" data-stop="Antipolo"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Masinag</h3>
                <span class="province-js">Antipolo</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- San Juan, Manila -->
          <div class="route-card-js" data-destination="San Juan, Manila" data-fare="₱350" data-stop="Cubao"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>San Juan</h3>
                <span class="province-js">Manila</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Legarda, Sampaloc, Manila -->
          <div class="route-card-js" data-destination="Legarda, Sampaloc, Manila" data-fare="₱350" data-stop="Sta. Mesa"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Legarda</h3>
                <span class="province-js">Sampaloc, Manila</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>

          <!-- Sta. Teresita, Sampaloc, Manila -->
          <div class="route-card-js" data-destination="Sta. Teresita, Sampaloc, Manila" data-fare="₱350" data-stop="Legarda"
            data-highlights='["ss","ss","ss"]'
            data-icons='["ss","ss","ss"]'>
            <div class="card-glass-js"></div>
            <div class="route-content-js">
              <div class="destination-marker-js">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="destination-info-js">
                <h3>Sta. Teresita</h3>
                <span class="province-js">Sampaloc, Manila</span>
              </div>
              <div class="route-meta-js">
                <span class="fare"><i class="fas fa-peso-sign"></i> ₱350</span>
              </div>
            </div>
            <button class="book-route-btn-js glass-effect-js" data-route-modal="loginRouteModal">
              <span>Book Now</span>
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </div>

        <button class="carousel-nav-js next-js" aria-label="Next destination">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>

      <div class="carousel-dots-js"></div>
      
    </section>


    <div class="destination-modal-js glass-modal-js" id="destinationInfoModal">
      <div class="modal-glass-backdrop-js"></div>

      <div class="modal-content-js">
        <button class="close-modal-js">&times;</button>
        <div class="modal-header-js">
          <div class="destination-icon-js">
            <i class="fas fa-map-marker-alt"></i>
          </div>
          <div>
            <h3 id="modalRouteDestinationName">Real</h3>
            <span class="province-js" id="modalRouteProvince">Quezon Province</span>
          </div>
        </div>

        <div class="modal-body-js">
          <div class="detail-cards-js">
            <div class="detail-card-js glass-effect-js">
              <i class="fas fa-bus"></i>
              <div>
                <span class="detail-label-js">Smooth Commute</span>
                <span class="detail-value-js" id="modalRouteTravelTime">3-4 hours</span>
              </div>
            </div>

            <div class="detail-card-js glass-effect-js">
              <i class="fas fa-peso-sign"></i>
              <div>
                <span class="detail-label-js">Fare</span>
                <span class="detail-value-js" id="modalRouteFare">₱350</span>
              </div>
            </div>

            <div class="detail-card-js glass-effect-js">
              <i class="fas fa-map-pin"></i>
              <div>
                <span class="detail-label-js">Popular Stop</span>
                <span class="detail-value-js" id="modalRoutePopularStop">Tanay</span>
              </div>
            </div>
          </div>

          <div class="route-highlights-js">
            <h4>Route Highlights</h4>
            <ul>
              <li><i class="fas fa-mountain"></i> Scenic mountain views</li>
              <li><i class="fas fa-far-ocean"></i> Rest stops with local cuisine</li>
              <li><i class="fas fa-wifi"></i> Free WiFi available</li>
            </ul>
          </div>
        </div>

        <div class="modal-footer-js">
          <button class="book-route-btn-js large-js glass-effect-js" data-route-modal="loginRouteModal">
            <span>Book This Route</span>
            <i class="fas fa-ticket-alt"></i>
          </button>
        </div>
      </div>
    </div>


    <section id="organization" class="section" data-animate="animate-fadeInUp" style="background-image: url('img/org-bg.png'); 
  background-size: cover; 
  background-position: center; 
  background-repeat: no-repeat; 
  height: 100vh;">
      <h2>Our Leadership Team</h2>
      <p class="org-description">Meet the dedicated team driving our mission to provide exceptional service and ensure your journey is seamless and comfortable.</p>

      <!-- Top Level - 2 Boxes -->
      <div class="slider" reverse="true" style="
  --width: 250px;
  --height: 300px;
  --quantity: 8;
  ">
        <div class="list">
          <div class="item" style="--position: 1"><img src="img/pres.png" alt=""></div>
          <div class="item" style="--position: 2"><img src="img/vp.png" alt=""></div>
          <div class="item" style="--position: 3"><img src="img/bod1.png" alt=""></div>
          <div class="item" style="--position: 4"><img src="img/bod2.png" alt=""></div>
          <div class="item" style="--position: 5"><img src="img/bod3.png" alt=""></div>
          <div class="item" style="--position: 6"><img src="img/bod4.png" alt=""></div>
          <div class="item" style="--position: 7"><img src="img/tres.png" alt=""></div>
          <div class="item" style="--position: 8"><img src="img/secr.png" alt=""></div>
        </div>
      </div>
    </section>





    <!-- Contact Section -->
    <section id="contact" class="section" data-animate="animate-fadeIn">
      <h2>Contact Us</h2>
      <div class="contact-container">
        <div class="contact-info">
          <div class="contact-item">
            <i class="fas fa-map-marker-alt"></i>
            <div>
              <p><strong>Address</strong></p>
              <p>Infanta: 02-A 20 de Julio St., Poblacion 38, Infanta, Quezon</p>
              <p>Manila: #0275 (A) Sta. Teresita, Sampaloc Terminal / REINNAVVODAI</p>
            </div>
          </div>
          <div class="contact-item">
            <i class="fas fa-phone"></i>
            <div>
              <p><strong>Phone</strong></p>
              <p>Infanta: 09610211441 | (042) 373-5022</p>
              <p>Manila: (Globe) 09674062207 | (Smart) 09286588163</p>
            </div>
          </div>
          <div class="contact-item">
            <i class="fas fa-envelope"></i>
            <div>
              <p><strong>Email</strong></p>
              <p>info@idk.com</p>
            </div>
          </div>
        </div>
        <div class="contact-links">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>
    </section>










<!-- User Guide Modal -->
<div id="vantastic-guide" class="vantastic-modal-container" style="display: none;">
  <div class="vantastic-modal-backdrop"></div>
  <div class="vantastic-modal-dialog">
    
    <h2 class="vantastic-modal-title">Fast as just 3 Step!</h2>
    
    <div class="vantastic-feature-grid">
      <div class="vantastic-feature-card">
        <div class="vantastic-feature-icon">
          <img src="svgs/lin.svg" alt="Book Ride" class="vantastic-icon-img" width="200" height="200">
        </div>
        <div class="vantastic-feature-desc">
          <h3 class="vantastic-feature-heading">Step 1: Register</h3>
          <p class="vantastic-feature-text"> Create your account in just 30 seconds with your email or phone number. 
  No lengthy forms - we only need basic details to get you started!.</p>
        </div>
      </div>
      
      <div class="vantastic-feature-card">
        <div class="vantastic-feature-icon">
          <img src="svgs/rig.svg" alt="Check Schedules" class="vantastic-icon-img" width="200" height="200">
        </div>
        <div class="vantastic-feature-desc">
          <h3 class="vantastic-feature-heading">Step 2: Login</h3>
          <p class="vantastic-feature-text">Access your personalized dashboard with your secure credentials. 
  Remember to verify your email for full account access.</p>
        </div>
      </div>
      
      <div class="vantastic-feature-card">
        <div class="vantastic-feature-icon">
          <img src="svgs/byt.svg" alt="Get Help" class="vantastic-icon-img" width="200" height="200">
        </div>
        <div class="vantastic-feature-desc">
          <h3 class="vantastic-feature-heading">Step 3: Start Booking</h3>
          <p class="vantastic-feature-text">Browse available routes, select your preferred time slot, and confirm your booking 
  with just a few taps. Your ride will be waiting!</p>
        </div>
      </div>
    </div>
    
    <div class="vantastic-modal-actions">
      <div class="vantastic-modal-remember">
        <input type="checkbox" id="vantastic-dont-show-again">
        <label for="vantastic-dont-show-again">Don't show this again</label>
      </div>
      <button id="vantastic-confirm-btn" class="vantastic-primary-btn">Got it, let's go!</button>
    </div>
  </div>
</div>

<style>
  /* Modal Container */
  .vantastic-modal-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    overflow-y: auto;
    padding: 1rem;
    box-sizing: border-box;
  }
  
  /* Modal Backdrop */
  .vantastic-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: -1;
  }
  
  /* Modal Dialog Box */
  .vantastic-modal-dialog {
    position: relative;
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    width: 100%;
    max-width: 56.25rem;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 0.3125rem 1.875rem rgba(0,0,0,0.3);
    text-align: center;
  }
  
  /* Close Button */
  .vantastic-modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 1.75rem;
    font-weight: bold;
    cursor: pointer;
    color: #666;
    transition: color 0.2s;
  }
  
  .vantastic-modal-close:hover {
    color: #333;
  }
  
  /* Title */
  .vantastic-modal-title {
    color: var(--vantastic-primary);
    margin-bottom: 1.25rem;
    font-size: 2rem;
  }
  
  /* Features Grid */
  .vantastic-feature-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1.875rem;
    margin: 1.875rem 0;
  }
  
  /* Feature Card */
  .vantastic-feature-card {
    flex: 1 1 15.625rem;
    max-width: 18.75rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
  
  /* Feature Icon */
  .vantastic-feature-icon {
    width: 12.5rem;
    height: 12.5rem;
    background-color: rgba(0, 180, 180, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.25rem;
    padding: 1.25rem;
    box-sizing: border-box;
    transition: transform 0.3s, background-color 0.3s;
  }
  
  .vantastic-feature-card:hover .vantastic-feature-icon {
    background-color: rgba(0, 180, 180, 0.2);
    transform: scale(1.05);
  }
  
  /* Icon Image */
  .vantastic-icon-img {
    width: 100%;
    height: auto;
    max-width: 100%;
    object-fit: contain;
  }
  
  /* Feature Heading */
  .vantastic-feature-heading {
    color: var(--vantastic-primary);
    margin-bottom: 0.9375rem;
    font-size: 1.5rem;
  }
  
  /* Feature Text */
  .vantastic-feature-text {
    color: #666;
    line-height: 1.5;
    font-size: 1.1rem;
  }
  
  /* Modal Actions */
  .vantastic-modal-actions {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    margin-top: 1.25rem;
  }
  
  /* Remember Option */
  .vantastic-modal-remember {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #666;
  }
  
  .vantastic-modal-remember input {
    cursor: pointer;
  }
  
  /* Primary Button */
  .vantastic-primary-btn {
    background-color: var(--teal);
    color: white;
    border: none;
    padding: 0.9375rem 2.5rem;
    border-radius: 3.125rem;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: bold;
  }
  
  .vantastic-primary-btn:hover {
    background-color: #008080;
    transform: translateY(-0.125rem);
    box-shadow: 0 0.3125rem 0.9375rem rgba(0, 180, 180, 0.3);
  }

  /* No Scroll Class */
  .vantastic-no-scroll {
    overflow: hidden;
  }

  /* Responsive Adjustments */
  @media (max-width: 48rem) {
    .vantastic-modal-dialog {
      padding: 1.25rem;
    }
    
    .vantastic-feature-icon {
      width: 10rem;
      height: 10rem;
    }
    
    .vantastic-feature-card {
      flex: 1 1 12.5rem;
    }
    
    .vantastic-modal-title {
      font-size: 1.8rem;
    }
    
    .vantastic-feature-heading {
      font-size: 1.3rem;
    }
    
    .vantastic-feature-text {
      font-size: 1rem;
    }
  }

  @media (max-width: 30rem) {
    .vantastic-feature-icon {
      width: 8rem;
      height: 8rem;
    }
    
    .vantastic-modal-dialog {
      padding: 1rem;
    }
    
    .vantastic-primary-btn {
      padding: 0.75rem 1.875rem;
      font-size: 1rem;
    }
  }
</style>

<script>
// VanTastic Guide Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
  const vantasticGuide = document.getElementById('vantastic-guide');
  const vantasticConfirmBtn = document.getElementById('vantastic-confirm-btn');
  const vantasticModalClose = document.querySelector('.vantastic-modal-close');
  const vantasticDontShowAgain = document.getElementById('vantastic-dont-show-again');
  
  // Check if user has previously chosen "Don't show again"
  if (!localStorage.getItem('vantasticGuideShown')) {
    // Show modal after a short delay (.5 second)
    setTimeout(() => {
      vantasticGuide.style.display = 'flex';
      document.body.classList.add('vantastic-no-scroll');
    }, 500);
  }
  
  // Close modal when clicking confirmation button
  vantasticConfirmBtn.addEventListener('click', function() {
    vantasticGuide.style.display = 'none';
    document.body.classList.remove('vantastic-no-scroll');
    
    // If "Don't show again" is checked, store preference
    if (vantasticDontShowAgain.checked) {
      localStorage.setItem('vantasticGuideShown', 'true');
    }
  });
  
  // Close modal when clicking the X
  vantasticModalClose.addEventListener('click', function() {
    vantasticGuide.style.display = 'none';
    document.body.classList.remove('vantastic-no-scroll');
    
    // If "Don't show again" is checked, store preference
    if (vantasticDontShowAgain.checked) {
      localStorage.setItem('vantasticGuideShown', 'true');
    }
  });
  
  // Close modal when clicking outside
  vantasticGuide.addEventListener('click', function(e) {
    if (e.target === vantasticGuide) {
      vantasticGuide.style.display = 'none';
      document.body.classList.remove('vantastic-no-scroll');
      
      // If "Don't show again" is checked, store preference
      if (vantasticDontShowAgain.checked) {
        localStorage.setItem('vantasticGuideShown', 'true');
      }
    }
  });
});
</script>

















    <!-- Login Modal -->
    <div id="login-modal" class="modal">
      <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Login</h2>
        <form id="login-form" method="post" action="login.php">
          <input type="email" name="email" placeholder="Email" required>
          <div class="password-container">
            <input type="password" id="login-password" name="password" placeholder="Password" required>
            <span class="show-password" onclick="togglePassword('login-password', this)">
              <i class="fas fa-eye-slash"></i>
            </span>
          </div>
          <button type="submit">Login <i class="fas fa-sign-in-alt"></i></button>
        </form>
        <div class="modal-footer">
          <p><a href="forgot-password.php">Forgot Password?</a></p>
        </div>
      </div>
    </div>

<!-- Register Modal -->
<div id="register-modal" class="modal">
  <div class="modal-content">
    <span class="close-modal">&times;</span>
    <h2>Register</h2>
    <form id="register-form" method="post" action="register.php">
      <input type="text" name="full_name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email" required>

      <div class="password-container">
        <input type="password" id="register-password" name="password" placeholder="Password" required>
        <span class="show-password" onclick="togglePassword('register-password', this)">
          <i class="fas fa-eye-slash"></i>
        </span>
      </div>

      <div class="password-container">
        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" required>
        <span class="show-password" onclick="togglePassword('confirm-password', this)">
          <i class="fas fa-eye-slash"></i>
        </span>
      </div>

      <div class="security-question-container">
        <p class="security-question-text">Security Question: Who is your first love?</p>
        <input type="text" name="security_answer" placeholder="Your answer" required>
        <small class="security-hint">This will be used to verify your identity if you get locked out.</small>
      </div>

      <button type="submit">Register <i class="fas fa-user-plus"></i></button>
    </form>
    <div class="modal-footer">
    </div>
  </div>
</div>



<!-- OTP Verification Modal -->
<div id="otp-modal" class="modal">
  <div class="modal-content">
    <span class="close-modal">&times;</span>
    <h2>Verify Your Email</h2>
    <p style="text-align: center; margin-bottom: 20px;">We've sent a 6-digit code to your email. Please enter it below:</p>
    
    <form id="otp-form" method="post" action="index.php">
      <input type="hidden" id="otp-email" name="email">
      
      <div class="otp-input-container">
        <input type="text" id="otp1" name="otp1" maxlength="1" pattern="[0-9]" required>
        <input type="text" id="otp2" name="otp2" maxlength="1" pattern="[0-9]" required>
        <input type="text" id="otp3" name="otp3" maxlength="1" pattern="[0-9]" required>
        <input type="text" id="otp4" name="otp4" maxlength="1" pattern="[0-9]" required>
        <input type="text" id="otp5" name="otp5" maxlength="1" pattern="[0-9]" required>
        <input type="text" id="otp6" name="otp6" maxlength="1" pattern="[0-9]" required>
      </div>
      
      <input type="hidden" id="full-otp" name="otp">
      
      <div class="otp-footer">
        <button typ="submit" class="verify-btn">Verify</button>
        <p>Didn't receive a code? <a href="#" id="resend-otp">Resend</a></p>
      </div>
    </form>
  </div>
</div>

<style>
  /* OTP Modal Styles */
  .otp-input-container {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin: 20px 0;
  }
  
  .otp-input-container input {
    width: 50px;
    height: 60px;
    text-align: center;
    font-size: 24px;
    border: 2px solid #0e386a;
    border-radius: 8px;
    background-color: rgba(255, 255, 255, 0.1);
    color: #0e386a;
    transition: all 0.3s;
  }
  
  .otp-input-container input:focus {
    outline: none;
    border-color: #4a90e2;
    box-shadow: 0 0 5px rgba(74, 144, 226, 0.5);
  }
  
  .verify-btn {
    background-color: #0e386a;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 50px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
    width: 100%;
    margin-top: 15px;
  }
  
  .verify-btn:hover {
    background-color: #4a90e2;
    transform: translateY(-2px);
  }
  
  .otp-footer {
    text-align: center;
    margin-top: 20px;
  }
  
  .otp-footer a {
    color: #0e386a;
    text-decoration: none;
    font-weight: 600;
  }
  
  .otp-footer a:hover {
    text-decoration: underline;
  }
</style>

<script>
// OTP Verification Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
  const otpModal = document.getElementById('otp-modal');
  const otpForm = document.getElementById('otp-form');
  const otpInputs = document.querySelectorAll('.otp-input-container input');
  const fullOtpInput = document.getElementById('full-otp');
  const resendOtpLink = document.getElementById('resend-otp');
  
  // Function to show OTP modal with email
function showOtpModal(email) {
  const otpModal = document.getElementById('otp-modal');
  document.getElementById('otp-email').value = email;
  otpModal.style.display = 'flex';
  document.body.classList.add('no-scroll');
  
  // Focus first OTP input
  setTimeout(() => {
    const firstInput = document.getElementById('otp1');
    if (firstInput) firstInput.focus();
  }, 100);
}
  
  // Function to close OTP modal
  function closeOtpModal() {
    otpModal.style.display = 'none';
    document.body.classList.remove('no-scroll');
  }
  
  // Close modal when clicking X
  otpModal.querySelector('.close-modal').addEventListener('click', closeOtpModal);
  
  // Close modal when clicking outside
  otpModal.addEventListener('click', function(e) {
    if (e.target === otpModal) {
      closeOtpModal();
    }
  });
  
  // OTP input handling
  otpInputs.forEach((input, index) => {
    // Allow only numbers
    input.addEventListener('input', function() {
      this.value = this.value.replace(/[^0-9]/g, '');
      
      // Auto-focus next input
      if (this.value.length === 1 && index < otpInputs.length - 1) {
        otpInputs[index + 1].focus();
      }
    });
    
    // Handle backspace
    input.addEventListener('keydown', function(e) {
      if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
        otpInputs[index - 1].focus();
      }
    });
    
    // Handle paste
    input.addEventListener('paste', function(e) {
      e.preventDefault();
      const pasteData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
      
      if (pasteData.length === 6) {
        for (let i = 0; i < 6; i++) {
          if (index + i < otpInputs.length) {
            otpInputs[index + i].value = pasteData[i];
          }
        }
      }
    });
  });
  
  // Combine OTP digits before form submission
  otpForm.addEventListener('submit', function(e) {
    let otp = '';
    otpInputs.forEach(input => {
      otp += input.value;
    });
    
    fullOtpInput.value = otp;
    
    // Basic validation
    if (otp.length !== 6) {
      e.preventDefault();
      alert('Please enter a complete 6-digit code');
      return;
    }
  });
  
  // Resend OTP functionality
  resendOtpLink.addEventListener('click', function(e) {
    e.preventDefault();
    const email = document.getElementById('otp-email').value;
    
    if (!email) {
      alert('Email not found. Please try registering again.');
      return;
    }
    
    fetch('resend_otp.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `email=${encodeURIComponent(email)}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('New OTP sent to your email!');
        
        // Clear existing OTP inputs
        otpInputs.forEach(input => {
          input.value = '';
        });
        
        // Focus first input
        otpInputs[0].focus();
      } else {
        alert(data.message || 'Failed to resend OTP. Please try again.');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred. Please try again.');
    });
  });
  
  // After successful registration, show OTP modal
  document.getElementById('register-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    
    fetch(form.action, {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showOtpModal(formData.get('email'));
      } else {
        showErrorModal(data.message || 'Registration failed. Please try again.');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showErrorModal('Network error. Please try again.');
    });
  });
});
</script>
































<!-- Security Answer Modal -->
<div id="security-answer-modal" class="modal vt-security-modal">
  <div class="modal-content vt-security-content">
    <span class="close-modal vt-security-close">&times;</span>
    <h2>Account Locked</h2>
    <p class="vt-security-email">Account: <span id="vt-display-email"></span></p>
    <p class="vt-security-message">Please answer your security question to unlock your account.</p>
    <form id="security-answer-form">
      <p class="vt-security-question">Who is your first love?</p>
      <input type="hidden" id="vt-locked-email">
      <input type="text" id="vt-security-answer" placeholder="Your answer" required>
      <button type="submit" class="vt-security-submit">Unlock Account</button>
    </form>
  </div>
</div>

<style>
  /* Security Answer Modal Styles */
.vt-security-modal {
  display: none;
  position: fixed;
  z-index: 1001;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.7);
}

.vt-security-content {
  background-color: #fefefe;
  margin: 10% auto;
  padding: 25px;
  border-radius: 8px;
  max-width: 400px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.2);
  border: 1px solid #0e386a;
}

.vt-security-close {
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.vt-security-close:hover,
.vt-security-close:focus {
  color: #0e386a;
  text-decoration: none;
  cursor: pointer;
}

.vt-security-message {
  color: #722f37;
  margin-bottom: 20px;
  font-weight: 500;
}

.vt-security-question {
  font-weight: 600;
  color: #0e386a;
  margin-bottom: 10px;
}

#vt-security-answer {
  width: 100%;
  padding: 12px;
  margin: 8px 0 20px;
  border: 1px solid #ddd;
  border-radius: 4px;
  box-sizing: border-box;
}

.vt-security-submit {
  width: 100%;
  background-color: #0e386a;
  color: white;
  padding: 14px 20px;
  margin: 8px 0;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 600;
}

.vt-security-submit:hover {
  background-color: #4a90e2;
}

.security-question-container {
  margin: 15px 0;
}

.security-question-text {
  font-weight: 600;
  color: #0e386a;
  margin-bottom: 5px;
}

.security-hint {
  display: block;
  font-size: 12px;
  color: #666;
  margin-top: 5px;
}

/* Error message and unlock button styles */
.vt-error-message {
  background-color: #f8d7da;
  color: #721c24;
  padding: 10px 15px;
  border-radius: 4px;
  margin: 15px 0;
  border: 1px solid #f5c6cb;
}

.vt-error-message p {
  margin: 0 0 10px 0;
}

.vt-unlock-now {
  background-color: #0e386a;
  color: white;
  border: none;
  padding: 8px 15px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.vt-unlock-now:hover {
  background-color: #4a90e2;
}

/* Security email display */
.vt-security-email {
  font-weight: 500;
  margin-bottom: 15px;
  color: #0e386a;
}

#vt-display-email {
  font-weight: 600;
}
</style>

<script>


// Handle security answer form submission
document.getElementById('security-answer-form').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const email = document.getElementById('vt-locked-email').value;
  const answer = document.getElementById('vt-security-answer').value;
  
  fetch('login.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `email=${encodeURIComponent(email)}&security_answer=${encodeURIComponent(answer)}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert(data.message);
      closeSecurityAnswerModal();
      // Clear the password field and focus it for new login attempt
      document.getElementById('login-password').value = '';
      document.getElementById('login-password').focus();
    } else {
      alert(data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
  });
});// Handle security answer form submission
document.getElementById('security-answer-form').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const email = document.getElementById('vt-locked-email').value;
  const answer = document.getElementById('vt-security-answer').value;
  
  fetch('login.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `email=${encodeURIComponent(email)}&security_answer=${encodeURIComponent(answer)}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert(data.message);
      closeSecurityAnswerModal();
      // Clear the password field and focus it for new login attempt
      document.getElementById('login-password').value = '';
      document.getElementById('login-password').focus();
    } else {
      alert(data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
  });
});










  // Handle security answer modal
function showSecurityAnswerModal(email) {
  const modal = document.getElementById('security-answer-modal');
  document.getElementById('vt-locked-email').value = email;
  document.getElementById('vt-display-email').textContent = email;
  modal.style.display = 'block';
}

function closeSecurityAnswerModal() {
  document.getElementById('security-answer-modal').style.display = 'none';
}

// Close modal when clicking X
document.querySelector('.vt-security-close').addEventListener('click', closeSecurityAnswerModal);

// Handle security answer form submission
document.getElementById('security-answer-form').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const email = document.getElementById('vt-locked-email').value;
  const answer = document.getElementById('vt-security-answer').value;
  
  fetch('login.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `email=${encodeURIComponent(email)}&security_answer=${encodeURIComponent(answer)}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert(data.message);
      closeSecurityAnswerModal();
      // Optionally, automatically submit the login form again
    } else {
      alert(data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
  });
});

</script>

    <!-- Login Loading Modal -->
    <div id="loginloading" class="modal">
      <div class="modal-content" style="text-align: center; max-width: 300px; background: #f8d7da; box-shadow: none;">
        <div class="containerofdotiss">
          <div class="dotiss"></div>
          <div class="dotiss"></div>
          <div class="dotiss"></div>
          <div class="dotiss"></div>
          <div class="dotiss"></div>
        </div>
        <svg width="0" height="0" class="svg">
          <defs>
            <filter id="uib-jelly-ooze">
              <feGaussianBlur in="SourceGraphic" stdDeviation="3" result="blur" />
              <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -7" result="ooze" />
              <feBlend in="SourceGraphic" in2="ooze" />
            </filter>
          </defs>
        </svg>
        <h3 style="color:  #0e386a; margin-top: 20px;">Logging you in...</h3>
      </div>
    </div>

    <style>
      #loginloading .modal-content {
        background: #f8d7da;
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 30px;
      }

      .containerofdotiss {
        --uib-size: 60px;
        --uib-color: #0e386a;
        --uib-speed: 2.6s;
        --uib-dot-size: calc(var(--uib-size) * 0.23);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: var(--uib-size);
        height: var(--uib-dot-size);
        filter: url('#uib-jelly-ooze');
        margin: 0 auto;
      }

      .dotiss {
        position: absolute;
        top: calc(50% - var(--uib-dot-size) / 2);
        left: calc(0px - var(--uib-dot-size) / 2);
        display: block;
        height: var(--uib-dot-size);
        width: var(--uib-dot-size);
        border-radius: 50%;
        background-color: var(--uib-color);
        animation: stream var(--uib-speed) linear infinite both;
        transition: background-color 0.3s ease;
      }

      .dotiss:nth-child(2) {
        animation-delay: calc(var(--uib-speed) * -0.2);
      }

      .dotiss:nth-child(3) {
        animation-delay: calc(var(--uib-speed) * -0.4);
      }

      .dotiss:nth-child(4) {
        animation-delay: calc(var(--uib-speed) * -0.6);
      }

      .dotiss:nth-child(5) {
        animation-delay: calc(var(--uib-speed) * -0.8);
      }

      @keyframes stream {

        0%,
        100% {
          transform: translateX(0) scale(0);
        }

        50% {
          transform: translateX(calc(var(--uib-size) * 0.5)) scale(1);
        }

        99.999% {
          transform: translateX(calc(var(--uib-size))) scale(0);
        }
      }

    </style>


    <!-- Success Modal -->
    <div id="success-modal" class="modal">
      <div class="modal-content" style="text-align: center; max-width: 500px;">
        <div class="success-animation">
          <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
            <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
            <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
          </svg>
          <div class="loading-bar" style="display: none;">
            <div class="loader"></div>
          </div>
        </div>
        <h2 style="color: #4CAF50; margin-top: 20px;">Registration Successful!</h2>
        <p id="success-message" style="margin: 20px 0; font-size: 16px; line-height: 1.5;">
          Please check your email to verify your account.
        </p>
        <button id="success-close-btn" style="
        background: #4CAF50;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        margin-top: 10px;
      ">Got it!</button>
      </div>
    </div>

    <!-- Error Modal -->
    <div id="error-modal" class="modal">
      <div class="modal-content" style="text-align: center; max-width: 500px;">
        <div class="error-animation">
          <svg class="crossmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
            <circle class="crossmark__circle" cx="26" cy="26" r="25" fill="none" />
            <path class="crossmark__cross" fill="none" d="M16 16 36 36 M36 16 16 36" />
          </svg>
        </div>
        <h2 style="color: #f44336; margin-top: 20px;">Error!</h2>
        <p id="error-message" style="margin: 20px 0; font-size: 16px; line-height: 1.5;"></p>
        <button id="error-close-btn" style="
        background: #f44336;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        margin-top: 10px;
      ">Try Again</button>
      </div>
    </div>

    <!-- Leaflet CSS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet JavaScript -->
    <script src="script.js"></script>
    <script src="routes.js"></script>
    <script src="scrollAnimate.js"></script>





<script>
// ==================== ABOUT US COUNTER ANIMATION ====================
document.addEventListener('DOMContentLoaded', function() {
  const statNumbers = document.querySelectorAll('.stat-number');
  
  const options = {
    threshold: 0.5
  };
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const statNumber = entry.target;
        const target = parseInt(statNumber.getAttribute('data-count'));
        const duration = 2000; // 2 seconds
        const step = target / (duration / 16); // 60fps
        
        let current = 0;
        const increment = () => {
          current += step;
          if (current < target) {
            statNumber.textContent = Math.floor(current);
            requestAnimationFrame(increment);
          } else {
            statNumber.textContent = target;
          }
        };
        
        increment();
        observer.unobserve(statNumber);
      }
    });
  }, options);
  
  statNumbers.forEach(stat => {
    observer.observe(stat);
  });

  // ==================== MENU TOGGLE FUNCTIONALITY ====================
  const loginRegisterBtn = document.querySelector('.login-register-btn');
  const navMenu2 = document.getElementById('nav-menu2');
  const mainMenuToggle = document.querySelector('.menu-toggle');
  const navMenu = document.getElementById('nav-menu');
  const body = document.body;

  // Create overlay elements if they don't exist
  const createOverlay = (className) => {
    let overlay = document.querySelector(`.${className}`);
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = className;
      document.body.appendChild(overlay);
    }
    return overlay;
  };

  const overlay = createOverlay('menu-overlay');
  const overlay2 = createOverlay('menu-overlay2');

  // Function to close all menus
  const closeAllMenus = () => {
    mainMenuToggle.classList.remove('active');
    navMenu.classList.remove('active');
    navMenu2.classList.remove('active');
    overlay.classList.remove('active');
    overlay2.classList.remove('active');
    body.classList.remove('no-scroll');
  };

  // Main menu toggle
  mainMenuToggle.addEventListener('click', function(e) {
    e.stopPropagation();
    this.classList.toggle('active');
    navMenu.classList.toggle('active');
    overlay.classList.toggle('active');
    body.classList.toggle('no-scroll');

    // Close account menu if open
    if (navMenu2.classList.contains('active')) {
      navMenu2.classList.remove('active');
      overlay2.classList.remove('active');
    }
  });

  // Account menu toggle
  loginRegisterBtn.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    navMenu2.classList.toggle('active');
    overlay2.classList.toggle('active');
    body.classList.toggle('no-scroll');

    // Close main menu if open
    if (navMenu.classList.contains('active')) {
      navMenu.classList.remove('active');
      overlay.classList.remove('active');
      mainMenuToggle.classList.remove('active');
    }
  });

  // Close menus when clicking on overlays
  overlay.addEventListener('click', closeAllMenus);
  overlay2.addEventListener('click', closeAllMenus);

  // Close menus when clicking on links
  document.querySelectorAll('#nav-menu a, #nav-menu2 a').forEach(link => {
    link.addEventListener('click', function(e) {
      if (!this.hasAttribute('data-modal')) {
        closeAllMenus();
      }
    });
  });

  // ==================== MODAL FUNCTIONALITY ====================
  const loginModal = document.getElementById('login-modal');
  const registerModal = document.getElementById('register-modal');
  const successModal = document.getElementById('success-modal');
  const errorModal = document.getElementById('error-modal');
  const closeModals = document.querySelectorAll('.close-modal');
  const loginLoadingModal = document.getElementById('loginloading');

  // Show modal function
  const showModal = (modal) => {
    closeAllMenus();
    modal.style.display = 'flex';
    body.classList.add('no-scroll');
  };

  // Close modal function
  const closeModal = (modal) => {
    modal.style.display = 'none';
    body.classList.remove('no-scroll');
  };

  // Login link click handler
  document.querySelector('.login-link').addEventListener('click', function(e) {
    e.preventDefault();
    showModal(loginModal);
  });

  // Register link click handler
  document.querySelector('.register-link').addEventListener('click', function(e) {
    e.preventDefault();
    showModal(registerModal);
  });

  // Close modals
  closeModals.forEach(closeBtn => {
    closeBtn.addEventListener('click', function() {
      const modal = this.closest('.modal');
      closeModal(modal);
    });
  });

  // Close modals when clicking outside
  window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
      closeModal(e.target);
    }
  });

// ==================== FORM HANDLING ====================
// Login form submission with loading animation
document.getElementById('login-form')?.addEventListener('submit', async function(e) {
  e.preventDefault();

  const form = this;
  const submitBtn = form.querySelector('button[type="submit"]');
  const email = form.querySelector('input[name="email"]').value;
  
  // Check if already submitting
  if (submitBtn.hasAttribute('data-submitting')) {
    return;
  }
  
  // Set submitting state
  submitBtn.setAttribute('data-submitting', 'true');
  submitBtn.disabled = true;
  const originalBtnText = submitBtn.innerHTML;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
  
  // Show loading modal if available
  if (typeof loginLoadingModal !== 'undefined' && loginLoadingModal) {
    loginLoadingModal.style.display = 'flex';
  }

  try {
    const formData = new FormData(form);
    const response = await fetch(form.action, {
      method: 'POST',
      body: formData,
      headers: {
        'Accept': 'application/json'
      }
    });

    const data = await response.json();

    if (data.needs_security_answer) {
      // Create a div for the error message with unlock button
      const errorDiv = document.createElement('div');
      errorDiv.className = 'vt-error-message';
      errorDiv.innerHTML = `
        <p>${data.message}</p>
        <button class="vt-unlock-now" 
                data-email="${data.locked_email || email}">Unlock Now</button>
      `;
      
      // Insert the error message near the login form
      const loginForm = document.getElementById('login-form');
      const existingError = loginForm.querySelector('.vt-error-message');
      if (existingError) {
        existingError.replaceWith(errorDiv);
      } else {
        loginForm.appendChild(errorDiv);
      }
      
      // Add event listener to the unlock button
      errorDiv.querySelector('.vt-unlock-now').addEventListener('click', function() {
        showSecurityAnswerModal(this.getAttribute('data-email'));
      });
      
      // Also show the security answer modal directly
      showSecurityAnswerModal(data.locked_email || email);
    } 
    else if (data.success) {
      window.location.href = data.redirect || 'customer-dashboard.php';
    } 
    else {
      // Show error in modal if available, otherwise use alert
      if (typeof showErrorModal !== 'undefined') {
        showErrorModal(data.message || 'Login failed. Please try again.');
      } else {
        alert(data.message || 'Login failed. Please try again.');
      }
    }
  } catch (error) {
    console.error('Login error:', error);
    if (typeof showErrorModal !== 'undefined') {
      showErrorModal('Network error. Please try again.');
    } else {
      alert('Network error. Please try again.');
    }
  } finally {
    // Hide loading modal if available
    if (typeof loginLoadingModal !== 'undefined' && loginLoadingModal) {
      loginLoadingModal.style.display = 'none';
    }
    
    // Reset button state after a minimum delay (1 second)
    setTimeout(() => {
      submitBtn.removeAttribute('data-submitting');
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnText;
    }, 1000);
  }
});

// Security answer modal handling
function showSecurityAnswerModal(email) {
  const modal = document.getElementById('security-answer-modal');
  if (!modal) return;
  
  document.getElementById('vt-locked-email').value = email;
  document.getElementById('vt-display-email').textContent = email;
  modal.style.display = 'block';
  
  // Focus on answer field when modal opens
  setTimeout(() => {
    const answerField = document.getElementById('vt-security-answer');
    if (answerField) answerField.focus();
  }, 100);
}

function closeSecurityAnswerModal() {
  const modal = document.getElementById('security-answer-modal');
  if (modal) modal.style.display = 'none';
}

// Close modal when clicking X
document.querySelector('.vt-security-close')?.addEventListener('click', closeSecurityAnswerModal);

// Handle security answer form submission
document.getElementById('security-answer-form')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const email = document.getElementById('vt-locked-email').value;
  const answer = document.getElementById('vt-security-answer').value;
  const submitBtn = this.querySelector('button[type="submit"]');
  
  // Set submitting state
  submitBtn.disabled = true;
  const originalBtnText = submitBtn.innerHTML;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
  
  try {
    const response = await fetch('login.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `email=${encodeURIComponent(email)}&security_answer=${encodeURIComponent(answer)}`
    });
    
    const data = await response.json();
    
    if (data.success) {
      if (typeof showSuccessModal !== 'undefined') {
        showSuccessModal(data.message || 'Account unlocked successfully!');
      } else {
        alert(data.message || 'Account unlocked successfully!');
      }
      
      closeSecurityAnswerModal();
      
      // Clear the password field and focus it for new login attempt
      const passwordField = document.getElementById('login-password');
      if (passwordField) {
        passwordField.value = '';
        passwordField.focus();
      }
      
      // Remove any existing error messages
      const errorDiv = document.querySelector('.vt-error-message');
      if (errorDiv) errorDiv.remove();
    } 
    else {
      if (typeof showErrorModal !== 'undefined') {
        showErrorModal(data.message || 'Incorrect security answer');
      } else {
        alert(data.message || 'Incorrect security answer');
      }
    }
  } 
  catch (error) {
    console.error('Error:', error);
    if (typeof showErrorModal !== 'undefined') {
      showErrorModal('An error occurred. Please try again.');
    } else {
      alert('An error occurred. Please try again.');
    }
  } 
  finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalBtnText;
  }
});


  // Register form submission
  document.getElementById('register-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    // Client-side validation
    const password = document.getElementById('register-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;

    if (password.length < 8) {
      showErrorModal('Password must be at least 8 characters long');
      return;
    }

    if (password !== confirmPassword) {
      showErrorModal('Passwords do not match');
      return;
    }

    const form = this;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;

    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form)
      });

      const data = await response.json();

      if (data.success) {
        showSuccessModal(data.message || 'Registration successful! Please check your email to verify your account.');
        form.reset();
      } else {
        showErrorModal(data.message || 'Registration failed. Please try again.');
      }
    } catch (error) {
      showErrorModal('Network error. Please try again.');
      console.error('Registration error:', error);
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnText;
    }
  });

  // Show success modal function
  function showSuccessModal(message) {
    const successModal = document.getElementById('success-modal');
    const loadingBar = successModal.querySelector('.loading-bar');
    const checkmark = successModal.querySelector('.checkmark');

    successModal.style.display = 'flex';
    checkmark.style.display = 'none';
    loadingBar.style.display = 'block';

    setTimeout(() => {
      loadingBar.style.display = 'none';
      checkmark.style.display = 'block';
      document.getElementById('success-message').textContent = message;
    }, 2000);
  }

  // Show error modal function
  function showErrorModal(message) {
    const errorModal = document.getElementById('error-modal');
    document.getElementById('error-message').textContent = message;
    errorModal.style.display = 'flex';
  }

  // Close success modal
  document.getElementById('success-close-btn')?.addEventListener('click', function() {
    closeModal(successModal);
  });

  // Close error modal
  document.getElementById('error-close-btn')?.addEventListener('click', function() {
    closeModal(errorModal);
  });

  // ==================== SCROLL BEHAVIOR ====================
  // Close menus when scrolling
  window.addEventListener('scroll', function() {
    if (window.scrollY > 50) {
      closeAllMenus();
    }
  });

  // ==================== VIDEO AT HERO ====================
  // Initialize all videos to be ready for hover
  const scenicItems = document.querySelectorAll('.scenic-item');

  scenicItems.forEach(item => {
    const video = item.querySelector('.scenic-video');

    // Preload video metadata
    video.load();

    item.addEventListener('mouseenter', function() {
      video.currentTime = 0;
      video.play().catch(e => console.log("Video play prevented:", e));
    });

    item.addEventListener('mouseleave', function() {
      video.pause();
    });
  });

  // Duplicate items for infinite loop effect
  const scenicList = document.querySelector('.scenic-list');
  const items = scenicList.querySelectorAll('.scenic-item');

  items.forEach(item => {
    const clone = item.cloneNode(true);
    scenicList.appendChild(clone);
  });
});
// Show/Hide Password Function
      function togglePassword(inputId, element) {
        const passwordInput = document.getElementById(inputId);
        const icon = element ? element.querySelector("i") : passwordInput.nextElementSibling.querySelector("i");

        if (passwordInput.type === "password") {
          passwordInput.type = "text";
          icon.classList.add("fa-eye");
          icon.classList.remove("fa-eye-slash");
        } else {
          passwordInput.type = "password";
          icon.classList.add("fa-eye-slash");
          icon.classList.remove("fa-eye");
        }
      }
</script>


  </body>
  </html>