document.addEventListener("DOMContentLoaded", function () {
  const slider = document.getElementById("slider");
  const slides = document.querySelectorAll(".slide");
  const dots = document.querySelectorAll(".dot");
  const prevBtn = document.querySelector(".prev");
  const nextBtn = document.querySelector(".next");

  let currentSlide = 0;
  let slideInterval;
  const slideDuration = 3000; // 3 seconds per slide

  // Load movies when page loads
  loadNowShowingMovies();

  // Initialize slider
  function startSlider() {
    slideInterval = setInterval(nextSlide, slideDuration);
    updateSlider();
  }

  // Go to specific slide
  function goToSlide(n) {
    currentSlide = n;
    updateSlider();
    resetInterval();
  }

  // Next slide
  function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    updateSlider();
  }

  // Previous slide
  function prevSlide() {
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    updateSlider();
  }

  // Update slider position and active dot
  function updateSlider() {
    slider.scrollTo({
      left: slides[currentSlide].offsetLeft,
      behavior: "smooth",
    });

    // Update active dot
    dots.forEach((dot) => dot.classList.remove("active"));
    dots[currentSlide].classList.add("active");
  }

  // Reset auto-slide interval
  function resetInterval() {
    clearInterval(slideInterval);
    startSlider();
  }

  // Event listeners
  prevBtn.addEventListener("click", prevSlide);
  nextBtn.addEventListener("click", nextSlide);

  dots.forEach((dot, index) => {
    dot.addEventListener("click", () => goToSlide(index));
  });

  // Pause on hover
  slider.addEventListener("mouseenter", () => {
    clearInterval(slideInterval);
  });

  // Resume on mouse leave
  slider.addEventListener("mouseleave", startSlider);

  // Start the slider
  startSlider();
});

function switchModal(closeId, openId) {
  closeModal(closeId);
  openModal(openId);
}

// Close modal when clicking outside
window.onclick = function (event) {
  const modals = document.querySelectorAll(".modal");
  modals.forEach((modal) => {
    if (event.target == modal) {
      modal.style.display = "none";
    }
  });
};

// Authentication functionality
document.addEventListener("DOMContentLoaded", function () {
  // Check if user is logged in
  checkLoginStatus();

  // Handle login form
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", handleLogin);
  }

  // Handle signup form
  const signupForm = document.getElementById("signupForm");
  if (signupForm) {
    signupForm.addEventListener("submit", handleSignup);
    setupFormValidation();
  }
});

// Setup real-time form validation
function setupFormValidation() {
  const signupPassword = document.getElementById("signupPassword");
  const confirmPassword = document.getElementById("signupConfirmPassword");
  const email = document.getElementById("signupEmail");
  const username = document.getElementById("signupUsername");

  // Password validation
  if (signupPassword) {
    signupPassword.addEventListener("input", function () {
      const password = this.value;
      if (password.length > 0 && password.length < 6) {
        this.classList.add("form-error");
        this.classList.remove("form-success");
      } else if (password.length >= 6) {
        this.classList.add("form-success");
        this.classList.remove("form-error");
      } else {
        this.classList.remove("form-error", "form-success");
      }
    });
  }

  // Confirm password validation
  if (confirmPassword && signupPassword) {
    confirmPassword.addEventListener("input", function () {
      const password = signupPassword.value;
      const confirm = this.value;
      if (confirm.length > 0) {
        if (password === confirm) {
          this.classList.add("form-success");
          this.classList.remove("form-error");
        } else {
          this.classList.add("form-error");
          this.classList.remove("form-success");
        }
      } else {
        this.classList.remove("form-error", "form-success");
      }
    });
  }

  // Email validation
  if (email) {
    email.addEventListener("input", function () {
      const emailValue = this.value;
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (emailValue.length > 0) {
        if (emailRegex.test(emailValue)) {
          this.classList.add("form-success");
          this.classList.remove("form-error");
        } else {
          this.classList.add("form-error");
          this.classList.remove("form-success");
        }
      } else {
        this.classList.remove("form-error", "form-success");
      }
    });
  }

  // Username validation
  if (username) {
    username.addEventListener("input", function () {
      const usernameValue = this.value;
      if (usernameValue.length > 0) {
        if (usernameValue.length >= 3) {
          this.classList.add("form-success");
          this.classList.remove("form-error");
        } else {
          this.classList.add("form-error");
          this.classList.remove("form-success");
        }
      } else {
        this.classList.remove("form-error", "form-success");
      }
    });
  }
}

// Check login status
function checkLoginStatus() {
  // Check if user is logged in by making a request to the server
  fetch("php/auth.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "action=check_session",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.user) {
        showUserInfo(data.user);
      } else {
        showAuthButtons();
      }
    })
    .catch((error) => {
      console.log("Session check failed:", error);
      showAuthButtons();
    });
}

// Handle login form submission
function handleLogin(e) {
  e.preventDefault();

  const username = document.getElementById("loginUsername").value;
  const password = document.getElementById("loginPassword").value;
  const messageDiv = document.getElementById("loginMessage");
  const submitButton = e.target.querySelector('button[type="submit"]');

  // Show loading state
  submitButton.textContent = "Logging in...";
  submitButton.disabled = true;
  messageDiv.textContent = "";

  const formData = new FormData();
  formData.append("action", "login");
  formData.append("username_or_email", username);
  formData.append("password", password);

  fetch("php/auth.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      messageDiv.textContent = data.message;
      messageDiv.className = data.success ? "message success" : "message error";

      if (data.success) {
        // Close modal and update UI
        setTimeout(() => {
          closeModal("loginModal");
          showUserInfo(data.user);
          document.getElementById("loginForm").reset();
          messageDiv.textContent = "";
        }, 1000);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      messageDiv.textContent = "Connection error. Please try again.";
      messageDiv.className = "message error";
    })
    .finally(() => {
      // Reset button state
      submitButton.textContent = "Log In";
      submitButton.disabled = false;
    });
}

// Handle signup form submission
function handleSignup(e) {
  e.preventDefault();

  const fullName = document.getElementById("signupFullName").value;
  const email = document.getElementById("signupEmail").value;
  const username = document.getElementById("signupUsername").value;
  const password = document.getElementById("signupPassword").value;
  const confirmPassword = document.getElementById(
    "signupConfirmPassword"
  ).value;
  const messageDiv = document.getElementById("signupMessage");
  const submitButton = e.target.querySelector('button[type="submit"]');

  // Client-side validation
  if (password !== confirmPassword) {
    messageDiv.textContent = "Passwords do not match!";
    messageDiv.className = "message error";
    return;
  }

  if (password.length < 6) {
    messageDiv.textContent = "Password must be at least 6 characters long!";
    messageDiv.className = "message error";
    return;
  }

  // Show loading state
  submitButton.textContent = "Creating Account...";
  submitButton.disabled = true;
  messageDiv.textContent = "";

  const formData = new FormData();
  formData.append("action", "register");
  formData.append("full_name", fullName);
  formData.append("email", email);
  formData.append("username", username);
  formData.append("password", password);
  formData.append("confirm_password", confirmPassword);

  fetch("php/auth.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      console.log("Response status:", response.status);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.text().then((text) => {
        console.log("Raw response:", text);
        try {
          return JSON.parse(text);
        } catch (e) {
          console.error("JSON parse error:", e);
          throw new Error("Invalid JSON response: " + text);
        }
      });
    })
    .then((data) => {
      console.log("Parsed data:", data);
      messageDiv.textContent = data.message;
      messageDiv.className = data.success ? "message success" : "message error";

      if (data.success) {
        // Clear form and switch to login
        document.getElementById("signupForm").reset();
        setTimeout(() => {
          switchModal("signupModal", "loginModal");
          document.getElementById("loginMessage").textContent =
            "Account created successfully! Please log in.";
          document.getElementById("loginMessage").className = "message success";
        }, 1500);
      }
    })
    .catch((error) => {
      console.error("Signup Error:", error);
      messageDiv.textContent = "Connection error: " + error.message;
      messageDiv.className = "message error";
    })
    .finally(() => {
      // Reset button state
      submitButton.textContent = "Create Account";
      submitButton.disabled = false;
    });
}

// Show user info in navbar
function showUserInfo(user) {
  const authButtons = document.getElementById("authButtons");
  const userInfo = document.getElementById("userInfo");
  const welcomeMessage = document.getElementById("welcomeMessage");
  const adminButton = document.getElementById("adminButton");

  if (authButtons && userInfo && welcomeMessage) {
    authButtons.style.display = "none";
    userInfo.style.display = "flex";
    welcomeMessage.textContent = `Welcome, ${user.full_name}!`;

    // Show admin button if user is admin
    if (user.is_admin && adminButton) {
      adminButton.style.display = "inline-block";
    } else if (adminButton) {
      adminButton.style.display = "none";
    }
  }
}

// Show auth buttons
function showAuthButtons() {
  const authButtons = document.getElementById("authButtons");
  const userInfo = document.getElementById("userInfo");
  const adminButton = document.getElementById("adminButton");

  if (authButtons && userInfo) {
    authButtons.style.display = "flex";
    userInfo.style.display = "none";

    // Hide admin button when logged out
    if (adminButton) {
      adminButton.style.display = "none";
    }
  }
}

// Logout function
function logout() {
  const formData = new FormData();
  formData.append("action", "logout");

  fetch("php/auth.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAuthButtons();
        // Redirect to home page
        window.location.href = "index.html";
      }
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}

// Toggle password visibility
function togglePassword(inputId) {
  const passwordInput = document.getElementById(inputId);
  const toggleButton = passwordInput.nextElementSibling;

  if (passwordInput.type === "password") {
    passwordInput.type = "text";
    toggleButton.innerHTML = "👁"; // Eye with hand - password visible (hide it)
  } else {
    passwordInput.type = "password";
    toggleButton.innerHTML = " 👁 "; // Eyes - password hidden (show it)
  }
}

// Clear form messages when modal is opened
function openModal(id) {
  document.getElementById(id).style.display = "block";

  // Clear any existing messages
  const messageDiv = document.getElementById(id.replace("Modal", "Message"));
  if (messageDiv) {
    messageDiv.textContent = "";
    messageDiv.className = "message";
  }

  // Reset form validation classes
  const form = document.getElementById(id.replace("Modal", "Form"));
  if (form) {
    const inputs = form.querySelectorAll("input");
    inputs.forEach((input) => {
      input.classList.remove("form-error", "form-success");
    });
  }
}

// Enhanced close modal function
function closeModal(id) {
  document.getElementById(id).style.display = "none";

  // Reset form
  const form = document.getElementById(id.replace("Modal", "Form"));
  if (form) {
    form.reset();

    // Reset validation classes
    const inputs = form.querySelectorAll("input");
    inputs.forEach((input) => {
      input.classList.remove("form-error", "form-success");
    });

    // Reset password toggles
    const passwordInputs = form.querySelectorAll('input[type="text"]');
    passwordInputs.forEach((input) => {
      if (input.id.includes("Password")) {
        input.type = "password";
        const toggleButton = input.nextElementSibling;
        if (
          toggleButton &&
          toggleButton.classList.contains("password-toggle")
        ) {
          toggleButton.innerHTML = "👀"; // Reset to eyes (password hidden)
        }
      }
    });
  }

  // Clear messages
  const messageDiv = document.getElementById(id.replace("Modal", "Message"));
  if (messageDiv) {
    messageDiv.textContent = "";
    messageDiv.className = "message";
  }
}

// Movie loading and display functions
function loadNowShowingMovies() {
  const loadingSpinner = document.getElementById("moviesLoading");

  if (loadingSpinner) {
    loadingSpinner.style.display = "block";
  }

  fetch("php/movies.php?action=now_showing")
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.movies) {
        displayMovies(data.movies);
      } else {
        showMoviesError("No movies currently showing.");
      }
    })
    .catch((error) => {
      console.error("Error loading movies:", error);
      showMoviesError("Failed to load movies. Please try again later.");
    })
    .finally(() => {
      if (loadingSpinner) {
        loadingSpinner.style.display = "none";
      }
    });
}

function displayMovies(movies) {
  const moviesContainer = document.getElementById("moviesContainer");

  if (!moviesContainer) return;

  moviesContainer.innerHTML = "";

  if (movies.length === 0) {
    moviesContainer.innerHTML = `
      <div class="no-movies">
        <p>No movies currently showing. Check back soon!</p>
      </div>
    `;
    return;
  }

  movies.forEach((movie) => {
    const movieElement = createMovieElement(movie);
    moviesContainer.appendChild(movieElement);
  });
}

function createMovieElement(movie) {
  const movieContainer = document.createElement("div");
  movieContainer.className = "movie-container";
  movieContainer.setAttribute("data-movie-id", movie.id);

  // Add click event to show movie details
  movieContainer.addEventListener("click", () => showMovieDetails(movie.id));

  movieContainer.innerHTML = `
    <div class="movie-img">
      <img src="${movie.image_url}" alt="${
    movie.title
  }" onerror="this.src='image/placeholder.jpg'" />
      ${movie.is_featured ? '<div class="featured-badge">Featured</div>' : ""}
    </div>
    <div class="info">
      <h4>${movie.title}</h4>
      <p>
        ${movie.description}
        <br />
        <strong>Genre:</strong> ${movie.genre}
        <br />
        <strong>Duration:</strong> ${movie.formatted_duration}
        <br />
        <strong>Rating:</strong> ${movie.rating}
        <br />
        <strong>Price:</strong> Rs. ${movie.ticket_price}
      </p>
      ${
        movie.show_times.length > 0
          ? `
        <div class="show-times">
          <strong>Show Times:</strong>
          ${movie.show_times
            .map((time) => `<span class="time-slot">${time}</span>`)
            .join("")}
        </div>
      `
          : ""
      }
    </div>
  `;

  return movieContainer;
}

function showMovieDetails(movieId) {
  fetch(`php/movies.php?action=movie_details&id=${movieId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.movie) {
        displayMovieModal(data.movie);
      } else {
        alert("Failed to load movie details.");
      }
    })
    .catch((error) => {
      console.error("Error loading movie details:", error);
      alert("Failed to load movie details.");
    });
}

function displayMovieModal(movie) {
  const movieDetails = document.getElementById("movieDetails");

  movieDetails.innerHTML = `
    <div class="movie-detail-header">
      <img src="${movie.image_url}" alt="${
    movie.title
  }" class="movie-detail-poster" />
      <div class="movie-detail-info">
        <h2>${movie.title}</h2>
        <p class="movie-meta">
          <span><strong>Genre:</strong> ${movie.genre}</span>
          <span><strong>Duration:</strong> ${movie.formatted_duration}</span>
          <span><strong>Rating:</strong> ${movie.rating}</span>
          <span><strong>Language:</strong> ${movie.language}</span>
        </p>
        <p class="movie-description">${movie.description}</p>
        <p><strong>Director:</strong> ${movie.director}</p>
        <p><strong>Cast:</strong> ${movie.cast}</p>
        <p><strong>Release Date:</strong> ${movie.release_date}</p>
        <p class="ticket-price"><strong>Ticket Price: Rs. ${
          movie.ticket_price
        }</strong></p>

        ${
          movie.show_times.length > 0
            ? `
          <div class="show-times-detail">
            <h4>Show Times:</h4>
            <div class="time-slots">
              ${movie.show_times
                .map(
                  (time) =>
                    `<button class="time-slot-btn" onclick="bookMovie(${movie.id}, '${time}')">${time}</button>`
                )
                .join("")}
            </div>
          </div>
        `
            : '<p class="no-shows">No shows available currently.</p>'
        }

        ${
          movie.trailer_url
            ? `
          <div class="trailer-section">
            <a href="${movie.trailer_url}" target="_blank" class="trailer-btn">Watch Trailer</a>
          </div>
        `
            : ""
        }
      </div>
    </div>
  `;

  openModal("movieModal");
}

function bookMovie(movieId, showTime) {
  // Redirect to booking page with movie details
  window.location.href = `booking.html?movie_id=${movieId}&show_time=${showTime}`;
}

function showMoviesError(message) {
  const moviesContainer = document.getElementById("moviesContainer");
  if (moviesContainer) {
    moviesContainer.innerHTML = `
      <div class="movies-error">
        <p>${message}</p>
        <button onclick="loadNowShowingMovies()" class="retry-btn">Retry</button>
      </div>
    `;
  }
}
