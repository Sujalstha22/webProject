document.addEventListener("DOMContentLoaded", function () {
  const slider = document.getElementById("slider");
  const slides = document.querySelectorAll(".slide");
  const dots = document.querySelectorAll(".dot");
  const prevBtn = document.querySelector(".prev");
  const nextBtn = document.querySelector(".next");

  let currentSlide = 0;
  let slideInterval;
  const slideDuration = 3000; // 3 seconds per slide

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
