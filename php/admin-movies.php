<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard - SSR Cinema</title>
    <link rel="stylesheet" href="style.css" />
    <style>
      .admin-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
      }
      
      .admin-tabs {
        display: flex;
        background: #2c2c2c;
        border-radius: 10px 10px 0 0;
        overflow: hidden;
        margin-bottom: 0;
      }
      
      .admin-tab {
        background: #2c2c2c;
        color: white;
        border: none;
        padding: 15px 25px;
        cursor: pointer;
        font-size: 16px;
        transition: background 0.3s ease;
        flex: 1;
      }
      
      .admin-tab:hover {
        background: #3c3c3c;
      }
      
      .admin-tab.active {
        background: #fa7e61;
      }
      
      .admin-content {
        background: #2c2c2c;
        border-radius: 0 0 10px 10px;
        padding: 30px;
        color: white;
      }
      
      .tab-panel {
        display: none;
      }
      
      .tab-panel.active {
        display: block;
      }
      
      .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
      }
      
      .stat-card {
        background: #1a1a1a;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        border-left: 4px solid #fa7e61;
      }
      
      .stat-number {
        font-size: 2.5em;
        font-weight: bold;
        color: #fa7e61;
        margin-bottom: 10px;
      }
      
      .stat-label {
        color: #ccc;
        font-size: 1.1em;
      }
      
      .movie-form {
        background: #1a1a1a;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 20px;
      }
      
      .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 15px;
      }
      
      .form-group {
        display: flex;
        flex-direction: column;
      }
      
      .form-group.full-width {
        grid-column: 1 / -1;
      }
      
      .form-group label {
        color: #fa7e61;
        margin-bottom: 5px;
        font-weight: bold;
      }
      
      .form-group input,
      .form-group textarea,
      .form-group select {
        padding: 12px;
        border: 2px solid #444;
        border-radius: 8px;
        background: #333;
        color: white;
        font-size: 14px;
      }
      
      .form-group input:focus,
      .form-group textarea:focus,
      .form-group select:focus {
        border-color: #fa7e61;
        outline: none;
        box-shadow: 0 0 5px rgba(250, 126, 97, 0.3);
      }
      
      .form-group textarea {
        resize: vertical;
        min-height: 100px;
      }
      
      .checkbox-group {
        display: flex;
        gap: 20px;
        align-items: center;
        margin-top: 10px;
      }
      
      .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
      }
      
      .checkbox-item input[type="checkbox"] {
        width: auto;
        margin: 0;
      }
      
      .show-times-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 10px;
        margin-top: 10px;
      }
      
      .time-input {
        padding: 8px;
        border: 2px solid #444;
        border-radius: 5px;
        background: #333;
        color: white;
        text-align: center;
      }
      
      .btn-primary {
        background: linear-gradient(135deg, #fa7e61 0%, #e66a4d 100%);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
      }
      
      .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(250, 126, 97, 0.4);
      }
      
      .btn-secondary {
        background: #6c757d;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        margin-left: 10px;
      }
      
      .btn-secondary:hover {
        background: #5a6268;
      }
      
      .movies-list {
        background: #1a1a1a;
        border-radius: 10px;
        overflow: hidden;
      }
      
      .movie-item {
        display: flex;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #333;
        transition: background 0.3s ease;
      }
      
      .movie-item:hover {
        background: #2a2a2a;
      }
      
      .movie-item:last-child {
        border-bottom: none;
      }
      
      .movie-poster {
        width: 60px;
        height: 90px;
        object-fit: cover;
        border-radius: 5px;
        margin-right: 15px;
      }
      
      .movie-info {
        flex: 1;
      }
      
      .movie-title {
        font-size: 1.2em;
        font-weight: bold;
        color: #fa7e61;
        margin-bottom: 5px;
      }
      
      .movie-details {
        color: #ccc;
        font-size: 0.9em;
      }
      
      .movie-actions {
        display: flex;
        gap: 10px;
      }
      
      .btn-small {
        padding: 5px 10px;
        font-size: 12px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
      }
      
      .btn-edit {
        background: #28a745;
        color: white;
      }
      
      .btn-delete {
        background: #dc3545;
        color: white;
      }
      
      .btn-toggle {
        background: #ffc107;
        color: #212529;
      }
      
      @media (max-width: 768px) {
        .form-row {
          grid-template-columns: 1fr;
        }
        
        .admin-tabs {
          flex-direction: column;
        }
        
        .show-times-container {
          grid-template-columns: repeat(2, 1fr);
        }
      }
    </style>
  </head>
  <body>
    <div class="admin-container">
      <h1 style="color: white; text-align: center; margin-bottom: 30px;">
        🎬 SSR Cinema Admin Dashboard
      </h1>
      
      <!-- Admin Tabs -->
      <div class="admin-tabs">
        <button class="admin-tab active" onclick="switchTab('dashboard')">
          📊 Dashboard
        </button>
        <button class="admin-tab" onclick="switchTab('add-movie')">
          ➕ Add Movie
        </button>
        <button class="admin-tab" onclick="switchTab('manage-movies')">
          🎭 Manage Movies
        </button>
      </div>
      
      <div class="admin-content">
        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-panel active">
          <h2>📈 Cinema Statistics</h2>
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-number" id="totalBookings">0</div>
              <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
              <div class="stat-number" id="moviesShowing">0</div>
              <div class="stat-label">Movies Showing</div>
            </div>
            <div class="stat-card">
              <div class="stat-number" id="totalRevenue">0</div>
              <div class="stat-label">Total Revenue (Rs.)</div>
            </div>
            <div class="stat-card">
              <div class="stat-number" id="ticketsToday">0</div>
              <div class="stat-label">Tickets Sold Today</div>
            </div>
          </div>
          
          <div style="text-align: center; margin-top: 30px;">
            <button class="btn-primary" onclick="refreshStats()">
              🔄 Refresh Statistics
            </button>
            <button class="btn-secondary" onclick="window.location.href='index.html'">
              🏠 Back to Home
            </button>
          </div>
        </div>
        
        <!-- Add Movie Tab -->
        <div id="add-movie" class="tab-panel">
          <h2>🎬 Add New Movie</h2>
          <form id="addMovieForm" class="movie-form">
            <div class="form-row">
              <div class="form-group">
                <label for="movieTitle">Movie Title *</label>
                <input type="text" id="movieTitle" name="title" required placeholder="Enter movie title">
              </div>
              <div class="form-group">
                <label for="movieDirector">Director</label>
                <input type="text" id="movieDirector" name="director" placeholder="Enter director name">
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="movieGenre">Genre</label>
                <input type="text" id="movieGenre" name="genre" placeholder="e.g., Action, Comedy, Drama">
              </div>
              <div class="form-group">
                <label for="movieLanguage">Language</label>
                <select id="movieLanguage" name="language">
                  <option value="English">English</option>
                  <option value="Hindi">Hindi</option>
                  <option value="Nepali">Nepali</option>
                  <option value="Other">Other</option>
                </select>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="movieDuration">Duration (minutes)</label>
                <input type="number" id="movieDuration" name="duration" min="60" max="300" value="120">
              </div>
              <div class="form-group">
                <label for="movieRating">Rating</label>
                <select id="movieRating" name="rating">
                  <option value="G">G - General Audiences</option>
                  <option value="PG">PG - Parental Guidance</option>
                  <option value="PG-13" selected>PG-13 - Parents Strongly Cautioned</option>
                  <option value="R">R - Restricted</option>
                  <option value="NC-17">NC-17 - Adults Only</option>
                </select>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="moviePrice">Ticket Price (Rs.)</label>
                <input type="number" id="moviePrice" name="ticket_price" min="100" max="2000" value="500" step="50">
              </div>
              <div class="form-group">
                <label for="movieImage">Image URL</label>
                <input type="text" id="movieImage" name="image_url" placeholder="e.g., image/poster4.jpg">
              </div>
            </div>
            
            <div class="form-group full-width">
              <label for="movieDescription">Description</label>
              <textarea id="movieDescription" name="description" placeholder="Enter movie description..."></textarea>
            </div>
            
            <div class="form-group full-width">
              <label>Show Times</label>
              <div class="show-times-container">
                <input type="time" class="time-input" name="show_time_1" value="10:00">
                <input type="time" class="time-input" name="show_time_2" value="14:00">
                <input type="time" class="time-input" name="show_time_3" value="18:00">
                <input type="time" class="time-input" name="show_time_4" value="21:30">
              </div>
            </div>
            
            <div class="form-group full-width">
              <div class="checkbox-group">
                <div class="checkbox-item">
                  <input type="checkbox" id="isShowing" name="is_showing" checked>
                  <label for="isShowing">Currently Showing</label>
                </div>
                <div class="checkbox-item">
                  <input type="checkbox" id="isFeatured" name="is_featured">
                  <label for="isFeatured">Featured Movie</label>
                </div>
              </div>
            </div>
            
            <div style="text-align: center; margin-top: 25px;">
              <button type="submit" class="btn-primary">
                ✨ Add Movie
              </button>
              <button type="reset" class="btn-secondary">
                🔄 Reset Form
              </button>
            </div>
          </form>
          
          <div id="addMovieMessage" class="message" style="margin-top: 20px;"></div>
        </div>
        
        <!-- Manage Movies Tab -->
        <div id="manage-movies" class="tab-panel">
          <h2>🎭 Manage Movies</h2>
          <div style="margin-bottom: 20px;">
            <button class="btn-primary" onclick="loadMoviesList()">
              🔄 Refresh Movies List
            </button>
          </div>
          
          <div id="moviesList" class="movies-list">
            <div style="text-align: center; padding: 40px; color: #ccc;">
              Click "Refresh Movies List" to load movies...
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Tab switching functionality
      function switchTab(tabName) {
        // Hide all tab panels
        document.querySelectorAll('.tab-panel').forEach(panel => {
          panel.classList.remove('active');
        });
        
        // Remove active class from all tabs
        document.querySelectorAll('.admin-tab').forEach(tab => {
          tab.classList.remove('active');
        });
        
        // Show selected tab panel
        document.getElementById(tabName).classList.add('active');
        
        // Add active class to clicked tab
        event.target.classList.add('active');
        
        // Load data for specific tabs
        if (tabName === 'dashboard') {
          loadStats();
        } else if (tabName === 'manage-movies') {
          loadMoviesList();
        }
      }

      // Load stats when page loads
      document.addEventListener("DOMContentLoaded", function () {
        loadStats();
      });

      // Load statistics from database
      function loadStats() {
        fetch("php/booking.php?action=get_stats")
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              document.getElementById("totalBookings").textContent = data.stats.total_bookings;
              document.getElementById("moviesShowing").textContent = data.stats.movies_showing;
              document.getElementById("totalRevenue").textContent = data.stats.total_revenue;
              document.getElementById("ticketsToday").textContent = data.stats.tickets_today;
            } else {
              console.error("Error loading statistics:", data.message);
            }
          })
          .catch((error) => {
            console.error("Error loading stats:", error);
          });
      }

      // Refresh statistics
      function refreshStats() {
        loadStats();
        showMessage("Statistics refreshed!", "success");
      }

      // Handle add movie form submission
      document.getElementById("addMovieForm").addEventListener("submit", function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append("action", "add_movie");
        
        // Get form values
        formData.append("title", document.getElementById("movieTitle").value);
        formData.append("director", document.getElementById("movieDirector").value);
        formData.append("genre", document.getElementById("movieGenre").value);
        formData.append("language", document.getElementById("movieLanguage").value);
        formData.append("duration", document.getElementById("movieDuration").value);
        formData.append("rating", document.getElementById("movieRating").value);
        formData.append("ticket_price", document.getElementById("moviePrice").value);
        formData.append("image_url", document.getElementById("movieImage").value);
        formData.append("description", document.getElementById("movieDescription").value);
        formData.append("is_showing", document.getElementById("isShowing").checked ? 1 : 0);
        formData.append("is_featured", document.getElementById("isFeatured").checked ? 1 : 0);
        
        // Get show times
        const showTimes = [];
        document.querySelectorAll('.time-input').forEach(input => {
          if (input.value) {
            // Convert 24-hour time to 12-hour format
            const time = new Date('1970-01-01T' + input.value + ':00');
            const timeString = time.toLocaleTimeString('en-US', {
              hour: 'numeric',
              minute: '2-digit',
              hour12: true
            });
            showTimes.push(timeString);
          }
        });
        formData.append("show_times", JSON.stringify(showTimes));
        
        // Submit form
        fetch("php/admin-movies.php", {
          method: "POST",
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          const messageDiv = document.getElementById("addMovieMessage");
          messageDiv.textContent = data.message;
          messageDiv.className = data.success ? "message success" : "message error";
          
          if (data.success) {
            document.getElementById("addMovieForm").reset();
            // Reset time inputs to default values
            document.querySelector('input[name="show_time_1"]').value = "10:00";
            document.querySelector('input[name="show_time_2"]').value = "14:00";
            document.querySelector('input[name="show_time_3"]').value = "18:00";
            document.querySelector('input[name="show_time_4"]').value = "21:30";
            document.getElementById("isShowing").checked = true;
            document.getElementById("isFeatured").checked = false;
          }
        })
        .catch(error => {
          console.error("Error:", error);
          const messageDiv = document.getElementById("addMovieMessage");
          messageDiv.textContent = "An error occurred. Please try again.";
          messageDiv.className = "message error";
        });
      });

      // Load movies list for management
      function loadMoviesList() {
        fetch("php/admin-movies.php?action=get_all_movies")
          .then(response => response.json())
          .then(data => {
            const moviesList = document.getElementById("moviesList");
            
            if (data.success && data.movies.length > 0) {
              moviesList.innerHTML = data.movies.map(movie => `
                <div class="movie-item">
                  <img src="${movie.image_url}" alt="${movie.title}" class="movie-poster" onerror="this.src='/placeholder.svg?height=90&width=60'">
                  <div class="movie-info">
                    <div class="movie-title">${movie.title}</div>
                    <div class="movie-details">
                      ${movie.genre} • ${movie.duration} min • ${movie.rating} • Rs. ${movie.ticket_price}
                      <br>
                      Status: ${movie.is_showing ? '🟢 Showing' : '🔴 Not Showing'} 
                      ${movie.is_featured ? '⭐ Featured' : ''}
                    </div>
                  </div>
                  <div class="movie-actions">
                    <button class="btn-small btn-toggle" onclick="toggleMovieStatus(${movie.id}, ${movie.is_showing})">
                      ${movie.is_showing ? 'Hide' : 'Show'}
                    </button>
                    <button class="btn-small btn-edit" onclick="toggleFeatured(${movie.id}, ${movie.is_featured})">
                      ${movie.is_featured ? 'Unfeature' : 'Feature'}
                    </button>
                    <button class="btn-small btn-delete" onclick="deleteMovie(${movie.id}, '${movie.title}')">
                      Delete
                    </button>
                  </div>
                </div>
              `).join('');
            } else {
              moviesList.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #ccc;">
                  No movies found. Add some movies first!
                </div>
              `;
            }
          })
          .catch(error => {
            console.error("Error loading movies:", error);
            document.getElementById("moviesList").innerHTML = `
              <div style="text-align: center; padding: 40px; color: #dc3545;">
                Error loading movies. Please try again.
              </div>
            `;
          });
      }

      // Toggle movie showing status
      function toggleMovieStatus(movieId, currentStatus) {
        const formData = new FormData();
        formData.append("action", "toggle_showing");
        formData.append("movie_id", movieId);
        formData.append("is_showing", currentStatus ? 0 : 1);
        
        fetch("php/admin-movies.php", {
          method: "POST",
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            loadMoviesList(); // Refresh the list
            showMessage(data.message, "success");
          } else {
            showMessage(data.message, "error");
          }
        })
        .catch(error => {
          console.error("Error:", error);
          showMessage("An error occurred", "error");
        });
      }

      // Toggle featured status
      function toggleFeatured(movieId, currentStatus) {
        const formData = new FormData();
        formData.append("action", "toggle_featured");
        formData.append("movie_id", movieId);
        formData.append("is_featured", currentStatus ? 0 : 1);
        
        fetch("php/admin-movies.php", {
          method: "POST",
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            loadMoviesList(); // Refresh the list
            showMessage(data.message, "success");
          } else {
            showMessage(data.message, "error");
          }
        })
        .catch(error => {
          console.error("Error:", error);
          showMessage("An error occurred", "error");
        });
      }

      // Delete movie
      function deleteMovie(movieId, movieTitle) {
        if (confirm(`Are you sure you want to delete "${movieTitle}"? This action cannot be undone.`)) {
          const formData = new FormData();
          formData.append("action", "delete_movie");
          formData.append("movie_id", movieId);
          
          fetch("php/admin-movies.php", {
            method: "POST",
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              loadMoviesList(); // Refresh the list
              showMessage(data.message, "success");
            } else {
              showMessage(data.message, "error");
            }
          })
          .catch(error => {
            console.error("Error:", error);
            showMessage("An error occurred", "error");
          });
        }
      }

      // Show message helper
      function showMessage(message, type) {
        // Create a temporary message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.zIndex = '9999';
        messageDiv.style.padding = '15px 20px';
        messageDiv.style.borderRadius = '5px';
        messageDiv.style.fontWeight = 'bold';
        
        document.body.appendChild(messageDiv);
        
        // Remove after 3 seconds
        setTimeout(() => {
          document.body.removeChild(messageDiv);
        }, 3000);
      }
    </script>
  </body>
</html>
