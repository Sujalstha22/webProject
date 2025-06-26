document.addEventListener("DOMContentLoaded", () => {
  const slider = document.getElementById("slider");
  const slides = document.querySelectorAll(".slide");
  const dots = document.querySelectorAll(".dot");
  const prevBtn = document.querySelector(".prev");
  const nextBtn = document.querySelector(".next");

  let currentSlide = 0;
  let slideInterval;
  const slideDuration = 3000;

  // Load movies and check login status when page loads
  loadNowShowingMovies();
  checkLoginStatus();

  // Initialize slider
  function startSlider() {
    if (slides.length > 0) {
      slideInterval = setInterval(nextSlide, slideDuration);
      updateSlider();
    }
  }

  function goToSlide(n) {
    currentSlide = n;
    updateSlider();
    resetInterval();
  }

  function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    updateSlider();
  }

  function prevSlide() {
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    updateSlider();
  }

  function updateSlider() {
    if (slider && slides.length > 0) {
      slider.scrollTo({
        left: slides[currentSlide].offsetLeft,
        behavior: "smooth",
      });

      dots.forEach((dot) => dot.classList.remove("active"));
      if (dots[currentSlide]) {
        dots[currentSlide].classList.add("active");
      }
    }
  }

  function resetInterval() {
    clearInterval(slideInterval);
    startSlider();
  }

  // Event listeners
  if (prevBtn) prevBtn.addEventListener("click", prevSlide);
  if (nextBtn) nextBtn.addEventListener("click", nextSlide);

  dots.forEach((dot, index) => {
    dot.addEventListener("click", () => goToSlide(index));
  });

  if (slider) {
    slider.addEventListener("mouseenter", () => {
      clearInterval(slideInterval);
    });
    slider.addEventListener("mouseleave", startSlider);
  }

  startSlider();

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

// Authentication functionality
let isAdminMode = false;

function setupFormValidation() {
  const signupPassword = document.getElementById("signupPassword");
  const confirmPassword = document.getElementById("signupConfirmPassword");
  const email = document.getElementById("signupEmail");
  const username = document.getElementById("signupUsername");

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

function checkLoginStatus() {
  console.log("🔍 Checking login status...");

  fetch("php/auth.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "action=check_session",
  })
    .then((response) => {
      console.log("📡 Session check response:", response.status);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      return response.text();
    })
    .then((text) => {
      console.log("📄 Raw session response:", text);
      try {
        return JSON.parse(text);
      } catch (e) {
        console.error("❌ Session JSON parse error:", e);
        throw new Error("Invalid session response");
      }
    })
    .then((data) => {
      console.log("✅ Session data:", data);

      if (data.success && data.user) {
        console.log("👤 User is logged in:", data.user.full_name);
        showUserInfo(data.user);
      } else {
        console.log("👤 User is not logged in");
        showAuthButtons();
      }
    })
    .catch((error) => {
      console.error("💥 Session check failed:", error);
      showAuthButtons();
    });
}

function handleLogin(e) {
  e.preventDefault();

  const username = document.getElementById("loginUsername").value.trim();
  const password = document.getElementById("loginPassword").value;
  const messageDiv = document.getElementById("loginMessage");
  const submitButton = e.target.querySelector('button[type="submit"]');

  if (!username || !password) {
    messageDiv.textContent = "Please enter both username/email and password.";
    messageDiv.className = "message error";
    return;
  }

  submitButton.textContent = "Logging in...";
  submitButton.disabled = true;
  messageDiv.textContent = "";

  const formData = new FormData();
  formData.append("action", "login");
  formData.append("username_or_email", username);
  formData.append("password", password);

  console.log("🔐 Attempting login for:", username);

  fetch("php/auth.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      console.log("📡 Login response status:", response.status);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      return response.text();
    })
    .then((text) => {
      console.log("📄 Raw login response:", text);
      try {
        return JSON.parse(text);
      } catch (e) {
        console.error("❌ JSON parse error:", e);
        throw new Error(
          "Invalid response from server: " + text.substring(0, 100)
        );
      }
    })
    .then((data) => {
      console.log("✅ Login data:", data);

      messageDiv.textContent = data.message;
      messageDiv.className = data.success ? "message success" : "message error";

      if (data.success) {
        console.log("🎉 Login successful, user:", data.user);

        if (isAdminMode) {
          if (data.user && data.user.is_admin) {
            setTimeout(() => {
              closeModal("loginModal");
              document.getElementById("loginForm").reset();
              messageDiv.textContent = "";
              window.location.href = "admin.html";
            }, 1000);
          } else {
            messageDiv.textContent =
              "Access denied. Admin privileges required.";
            messageDiv.className = "message error";
          }
        } else {
          setTimeout(() => {
            closeModal("loginModal");
            showUserInfo(data.user);
            document.getElementById("loginForm").reset();
            messageDiv.textContent = "";
          }, 1000);
        }
      } else {
        console.error("❌ Login failed:", data.message);
      }
    })
    .catch((error) => {
      console.error("💥 Login error:", error);
      messageDiv.textContent = "Connection error: " + error.message;
      messageDiv.className = "message error";
    })
    .finally(() => {
      submitButton.textContent = "Log In";
      submitButton.disabled = false;
    });
}

function handleSignup(e) {
  e.preventDefault();

  const fullName = document.getElementById("signupFullName").value.trim();
  const email = document.getElementById("signupEmail").value.trim();
  const username = document.getElementById("signupUsername").value.trim();
  const password = document.getElementById("signupPassword").value;
  const confirmPassword = document.getElementById(
    "signupConfirmPassword"
  ).value;
  const messageDiv = document.getElementById("signupMessage");
  const submitButton = e.target.querySelector('button[type="submit"]');

  // Client-side validation
  if (!fullName || !email || !username || !password || !confirmPassword) {
    messageDiv.textContent = "All fields are required!";
    messageDiv.className = "message error";
    return;
  }

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

  if (username.length < 3) {
    messageDiv.textContent = "Username must be at least 3 characters long!";
    messageDiv.className = "message error";
    return;
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    messageDiv.textContent = "Please enter a valid email address!";
    messageDiv.className = "message error";
    return;
  }

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

  console.log("📝 Attempting signup for:", username, email);

  fetch("php/auth.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      console.log("📡 Signup response status:", response.status);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log("✅ Signup data:", data);

      messageDiv.textContent = data.message;
      messageDiv.className = data.success ? "message success" : "message error";

      if (data.success) {
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
      console.error("💥 Signup error:", error);
      messageDiv.textContent = "Connection error: " + error.message;
      messageDiv.className = "message error";
    })
    .finally(() => {
      submitButton.textContent = "Create Account";
      submitButton.disabled = false;
    });
}

function showUserInfo(user) {
  console.log("👤 Showing user info for:", user.full_name);

  const authButtons = document.getElementById("authButtons");
  const userInfo = document.getElementById("userInfo");
  const welcomeMessage = document.getElementById("welcomeMessage");
  const adminButton = document.getElementById("adminButton");
  const adminNavLink = document.getElementById("adminNavLink");

  // Hide login/signup buttons
  if (authButtons) {
    authButtons.style.display = "none";
    console.log("🔒 Hidden auth buttons");
  }

  // Show user info
  if (userInfo) {
    userInfo.style.display = "flex";
    console.log("👋 Showing user info");
  }

  // Set welcome message
  if (welcomeMessage) {
    welcomeMessage.textContent = `Welcome, ${user.full_name}!`;
    console.log("💬 Set welcome message");
  }

  // Show admin features if user is admin
  if (user.is_admin) {
    console.log("👑 User is admin, showing admin features");
    if (adminButton) {
      adminButton.style.display = "inline-block";
    }
    if (adminNavLink) {
      adminNavLink.style.display = "inline-block";
    }
  } else {
    console.log("👤 Regular user, hiding admin features");
    if (adminButton) {
      adminButton.style.display = "none";
    }
    if (adminNavLink) {
      adminNavLink.style.display = "none";
    }
  }
}

function showAuthButtons() {
  console.log("🔓 Showing auth buttons (user logged out)");

  const authButtons = document.getElementById("authButtons");
  const userInfo = document.getElementById("userInfo");
  const adminButton = document.getElementById("adminButton");
  const adminNavLink = document.getElementById("adminNavLink");

  // Show login/signup buttons
  if (authButtons) {
    authButtons.style.display = "flex";
  }

  // Hide user info
  if (userInfo) {
    userInfo.style.display = "none";
  }

  // Hide admin elements when logged out
  if (adminButton) {
    adminButton.style.display = "none";
  }
  if (adminNavLink) {
    adminNavLink.style.display = "none";
  }
}

function logout() {
  console.log("🚪 Logging out...");

  const formData = new FormData();
  formData.append("action", "logout");

  fetch("php/auth.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("✅ Logout response:", data);
      if (data.success) {
        showAuthButtons();
        // Optionally reload the page to reset everything
        window.location.reload();
      }
    })
    .catch((error) => {
      console.error("💥 Logout error:", error);
    });
}

function togglePassword(inputId) {
  const passwordInput = document.getElementById(inputId);
  const toggleButton = passwordInput.nextElementSibling;

  if (passwordInput.type === "password") {
    passwordInput.type = "text";
    toggleButton.innerHTML = "👁";
  } else {
    passwordInput.type = "password";
    toggleButton.innerHTML = "👀";
  }
}

function openModal(id) {
  document.getElementById(id).style.display = "block";

  const messageDiv = document.getElementById(id.replace("Modal", "Message"));
  if (messageDiv) {
    messageDiv.textContent = "";
    messageDiv.className = "message";
  }

  const form = document.getElementById(id.replace("Modal", "Form"));
  if (form) {
    const inputs = form.querySelectorAll("input");
    inputs.forEach((input) => {
      input.classList.remove("form-error", "form-success");
    });
  }
}

function closeModal(id) {
  document.getElementById(id).style.display = "none";

  const form = document.getElementById(id.replace("Modal", "Form"));
  if (form) {
    form.reset();

    const inputs = form.querySelectorAll("input");
    inputs.forEach((input) => {
      input.classList.remove("form-error", "form-success");
    });

    const passwordInputs = form.querySelectorAll('input[type="text"]');
    passwordInputs.forEach((input) => {
      if (input.id.includes("Password")) {
        input.type = "password";
        const toggleButton = input.nextElementSibling;
        if (
          toggleButton &&
          toggleButton.classList.contains("password-toggle")
        ) {
          toggleButton.innerHTML = "👀";
        }
      }
    });
  }

  const messageDiv = document.getElementById(id.replace("Modal", "Message"));
  if (messageDiv) {
    messageDiv.textContent = "";
    messageDiv.className = "message";
  }

  if (id === "loginModal") {
    resetLoginModal();
  }
}

function switchModal(closeId, openId) {
  closeModal(closeId);
  openModal(openId);
}

function toggleAdminMode() {
  isAdminMode = !isAdminMode;

  const modalTitle = document.querySelector("#loginModal h2");
  const usernameInput = document.getElementById("loginUsername");
  const passwordInput = document.getElementById("loginPassword");
  const submitBtn = document.getElementById("loginSubmitBtn");
  const adminBtn = document.getElementById("adminModeBtn");
  const signupLink = document.getElementById("signupLink");

  if (isAdminMode) {
    modalTitle.innerHTML = "Admin Login";
    usernameInput.placeholder = "Admin Username";
    passwordInput.placeholder = "Admin Password";
    submitBtn.innerHTML = "Access Admin Dashboard";
    submitBtn.style.background =
      "linear-gradient(135deg, #fa7e61 0%, #e66a4d 100%)";
    submitBtn.style.fontWeight = "600";
    adminBtn.innerHTML = "Switch to User Login";
    adminBtn.style.background = "#6c757d";
    signupLink.style.display = "none";

    if (!document.getElementById("adminNotice")) {
      const notice = document.createElement("p");
      notice.id = "adminNotice";
      notice.style.textAlign = "center";
      notice.style.color = "#fa7e61";
      notice.style.marginBottom = "20px";
      notice.style.fontSize = "14px";
      notice.innerHTML = "Administrator Access Only";
      modalTitle.parentNode.insertBefore(notice, modalTitle.nextSibling);
    }
  } else {
    resetLoginModal();
  }
}

function resetLoginModal() {
  isAdminMode = false;

  const modalTitle = document.querySelector("#loginModal h2");
  const usernameInput = document.getElementById("loginUsername");
  const passwordInput = document.getElementById("loginPassword");
  const submitBtn = document.getElementById("loginSubmitBtn");
  const adminBtn = document.getElementById("adminModeBtn");
  const signupLink = document.getElementById("signupLink");
  const adminNotice = document.getElementById("adminNotice");

  if (modalTitle) modalTitle.innerHTML = "Login";
  if (usernameInput) usernameInput.placeholder = "Username or Email";
  if (passwordInput) passwordInput.placeholder = "Password";
  if (submitBtn) {
    submitBtn.innerHTML = "Log In";
    submitBtn.style.background = "";
    submitBtn.style.fontWeight = "";
  }
  if (adminBtn) {
    adminBtn.innerHTML = "Switch to Admin Login";
    adminBtn.style.background =
      "linear-gradient(135deg, #fa7e61 0%, #e66a4d 100%)";
  }
  if (signupLink) signupLink.style.display = "block";
  if (adminNotice) adminNotice.remove();
}

// Close modal when clicking outside
window.onclick = (event) => {
  const modals = document.querySelectorAll(".modal");
  modals.forEach((modal) => {
    if (event.target == modal) {
      modal.style.display = "none";
    }
  });
};

// Movie loading functions
function loadNowShowingMovies() {
  console.log("🎬 Loading movies...");

  const loadingSpinner = document.getElementById("moviesLoading");
  const moviesContainer = document.getElementById("moviesContainer");

  if (loadingSpinner) {
    loadingSpinner.style.display = "block";
    loadingSpinner.textContent = "Loading movies...";
  }

  if (moviesContainer) {
    moviesContainer.innerHTML =
      '<div style="color: white; text-align: center; padding: 20px;">Loading movies...</div>';
  }

  fetch("php/movies.php?action=now_showing")
    .then((response) => {
      console.log("📡 Movies response status:", response.status);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      return response.text();
    })
    .then((text) => {
      console.log("📄 Raw movies response:", text.substring(0, 500) + "...");

      // Check if response looks like JSON
      if (!text.trim().startsWith("{") && !text.trim().startsWith("[")) {
        console.error("❌ Response is not JSON:", text);
        throw new Error(
          "Server returned HTML instead of JSON. Check php/movies.php for errors."
        );
      }

      try {
        return JSON.parse(text);
      } catch (e) {
        console.error("❌ JSON parse error:", e);
        console.error("Raw text:", text);
        throw new Error("Invalid JSON response from movies API");
      }
    })
    .then((data) => {
      console.log("✅ Movies data:", data);

      if (data.success) {
        if (data.movies && data.movies.length > 0) {
          console.log(`🎭 Found ${data.movies.length} movies`);
          displayMovies(data.movies);
        } else {
          console.log("📭 No movies found");
          showMoviesError(
            "No movies currently showing. <a href='config/database.php' target='_blank' style='color: #fa7e61;'>Click here to setup database</a>"
          );
        }
      } else {
        console.error("❌ Movies API error:", data.message);
        const errorMessage = data.message || "Failed to load movies.";
        showMoviesError(errorMessage);
      }
    })
    .catch((error) => {
      console.error("💥 Movies fetch error:", error);
      showMoviesError(
        `Failed to load movies: ${error.message}<br><a href='config/database.php' target='_blank' style='color: #fa7e61;'>Setup Database</a>`
      );
    })
    .finally(() => {
      if (loadingSpinner) {
        loadingSpinner.style.display = "none";
      }
    });
}

function displayMovies(movies) {
  console.log("🎨 Displaying", movies.length, "movies");

  const moviesContainer = document.getElementById("moviesContainer");
  if (!moviesContainer) {
    console.error("❌ Movies container not found");
    return;
  }

  moviesContainer.innerHTML = "";

  if (movies.length === 0) {
    moviesContainer.innerHTML = `
      <div class="no-movies" style="color: white; text-align: center; padding: 40px;">
        <p>No movies currently showing. Check back soon!</p>
      </div>
    `;
    return;
  }

  movies.forEach((movie, index) => {
    console.log(`🎬 Creating element for movie ${index + 1}:`, movie.title);
    const movieElement = createMovieElement(movie);
    moviesContainer.appendChild(movieElement);
  });

  console.log("✅ All movies displayed successfully");
}

function createMovieElement(movie) {
  const movieContainer = document.createElement("div");
  movieContainer.className = "movie-container";
  movieContainer.setAttribute("data-movie-id", movie.id);

  movieContainer.addEventListener("click", () => showMovieDetails(movie.id));

  movieContainer.innerHTML = `
    <div class="movie-img">
      <img src="${movie.image_url}" alt="${
    movie.title
  }" onerror="this.src='/placeholder.svg?height=400&width=300'" />
      ${movie.is_featured ? '<div class="featured-badge">Featured</div>' : ""}
    </div>
    <div class="info">
      <h4>${movie.title}</h4>
      <p>
        ${movie.description}
        <br />
        <strong>Genre:</strong> ${movie.genre}
        <br />
        <strong>Duration:</strong> ${
          movie.formatted_duration || movie.duration + "min"
        }
        <br />
        <strong>Rating:</strong> ${movie.rating}
        <br />
        <strong>Price:</strong> Rs. ${movie.ticket_price}
      </p>
      ${
        movie.show_times && movie.show_times.length > 0
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
          <span><strong>Duration:</strong> ${
            movie.formatted_duration || movie.duration + "min"
          }</span>
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
          movie.show_times && movie.show_times.length > 0
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
  window.location.href = `booking.html?movie_id=${movieId}&show_time=${showTime}`;
}

function showMoviesError(message) {
  console.log("⚠️ Showing movies error:", message);

  const moviesContainer = document.getElementById("moviesContainer");
  if (moviesContainer) {
    moviesContainer.innerHTML = `
      <div class="movies-error" style="color: white; text-align: center; padding: 40px; background: #2c2c2c; border-radius: 10px; margin: 20px;">
        <p>${message}</p>
        <button onclick="loadNowShowingMovies()" class="retry-btn" style="background: #fa7e61; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 10px;">Retry</button>
      </div>
    `;
  }
}
