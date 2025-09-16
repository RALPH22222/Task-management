<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Interactive Task Management System</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="stylesheet" href="index.css" />
</head>
<body>
  <header class="navbar">
    <div class="logo">
      <img src="logo.png" alt="University Logo" />
      <h1>Task Management System</h1>
    </div>
    <nav class="nav-links">
      <a href="#home">Home</a>
      <a href="#features">Features</a>
      <a href="#about">About</a>
      <a href="#developers">Developers</a>
      <a href="auth/login.php">Login</a>
      <a href="auth/register.php">Register</a>
    </nav>
    <button class="mobile-menu-btn">
      <i class="fas fa-bars"></i>
    </button>
  </header>

  <section id="home" class="hero">
    <div class="container">
      <div class="hero-content">
        <h2>Organize Your Workflow, Meet Your Deadlines</h2>
        <p>
          A calming, easy-to-use platform designed to optimize your productivity
          and collaboration. Perfect for academic environments and research teams.
        </p>
        <div class="hero-buttons">
          <a href="auth/register.php" class="btn primary-btn">Get Started Free</a>
          <a href="#features" class="btn secondary-btn">Learn More</a>
        </div>
      </div>
    </div>
  </section>

  <section id="features" class="features">
    <div class="container">
      <h3 class="section-title">Why Choose Our System?</h3>
      <div class="feature-grid">
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-tasks"></i>
          </div>
          <h4>Task Tracking</h4>
          <p>Stay on top of tasks with clear deadlines, priorities, and progress tracking in a simple, intuitive interface.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-users"></i>
          </div>
          <h4>Team Collaboration</h4>
          <p>Work seamlessly with managers and teammates in real-time. Assign tasks, share files, and communicate efficiently.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">
            <i class="fas fa-bell"></i>
          </div>
          <h4>Smart Notifications</h4>
          <p>Never miss an important update or approaching deadline. Customize how and when you receive alerts.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="developers" class="developers">
    <div class="container">
      <h3 class="section-title">Development Team Contributions</h3>
      <div class="developer-carousel" aria-roledescription="carousel">
        <div class="carousel-track">
          <div class="carousel-item">
            <div class="developer-card">
              <div class="developer-image">
                <img src="https://scontent.fcgy1-2.fna.fbcdn.net/v/t39.30808-1/481461482_1684307262444063_3651296373906745467_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=108&ccb=1-7&_nc_sid=e99d92&_nc_eui2=AeE1NNcbWj6ezY3xd6s_OM2kgTg3ddQIyqaBODd11AjKpoaF9mpEu_xpHPAOrCR3z9JCIVM2Uup643aVvIaWZc55&_nc_ohc=6FDKcl2RSmoQ7kNvwGm3AO-&_nc_oc=AdnmxxQWZHVUt3l9lF8m7WOifMVbnPRnMFxAA9u-O9qo9W-bH14-vQnK60btrrx1mPg&_nc_zt=24&_nc_ht=scontent.fcgy1-2.fna&_nc_gid=eTrGYC0X5V11ePgdADuWEg&oh=00_AfavcsIQi4AMy1DU8_tDPZC9xshPW-C-yCPgAVYaFZ4UeA&oe=68CBBFB2" alt="Clarence John Rivero">
              </div>
              <h4>Clarence John Rivero</h4>
              <p class="developer-role">Lead Frontend Developer</p>
              <p>Architected the responsive UI components and implemented the task management dashboard with real-time updates and intuitive navigation.</p>
              <div class="developer-contributions">
                <span class="contribution-tag">UI/UX Design</span>
                <span class="contribution-tag">Frontend Architecture</span>
                <span class="contribution-tag">Dashboard Development</span>
              </div>
            </div>
          </div>
          <div class="carousel-item">
            <div class="developer-card">
              <div class="developer-image">
                <img src="https://scontent.fcgy1-1.fna.fbcdn.net/v/t39.30808-1/525327197_1037970828509197_7340734303060396891_n.jpg?stp=dst-jpg_s100x100_tt6&_nc_cat=105&ccb=1-7&_nc_sid=e99d92&_nc_eui2=AeEBfphak9IBkAfjwUDxHWXO4XxASoy1wmbhfEBKjLXCZliIlR60iUScK2VlMIAYHnimJsO1LY7ExFWZ2CfZPTnb&_nc_ohc=S3x4A1BBHu0Q7kNvwGV-xQ4&_nc_oc=AdlRuoUYoqMKe6ZWbwI1gW-5B8BtilUXDImt0s4QZiimnYoT3VCehBlKBfGmiSCt1Ls&_nc_zt=24&_nc_ht=scontent.fcgy1-1.fna&_nc_gid=ZPuQR9THy7EaEr60afDvxg&oh=00_AfaNe-xSvHuhIFK5dR-VwX0JJ__EfjD4j_jXL_TxW6XNsw&oe=68CBABE5" alt="Dan david Hernandez">
              </div>
              <h4>Dan David Hernandez</h4>
              <p class="developer-role">UX/UI Designer & Frontend Specialist</p>
              <p>Designed the user interface for seamless task creation, project management, and team collaboration features with focus on academic workflows.</p>
              <div class="developer-contributions">
                <span class="contribution-tag">User Research</span>
                <span class="contribution-tag">Wireframing</span>
                <span class="contribution-tag">Prototyping</span>
              </div>
            </div>
          </div>
          <div class="carousel-item">
            <div class="developer-card">
              <div class="developer-image">
                <img src="https://scontent.fcgy1-3.fna.fbcdn.net/v/t39.30808-6/487196451_122229546614032561_1399478855613180461_n.jpg?_nc_cat=111&ccb=1-7&_nc_sid=6ee11a&_nc_eui2=AeFHqHCqZD4OzDwvCFKgBGO0mOQrAR0DASCY5CsBHQMBID52wdGMSLI7LDTcqXj0iQGmqi_hRNZrnNbpAlITfAjg&_nc_ohc=LUQRwfkroz4Q7kNvwEOZirx&_nc_oc=Adl3LLj9ybz-TUN2CDS7tJtNkpviTEwAIUu0FJrXcPNqn7vw5IuSyvTHep-4MmuxIfg&_nc_zt=23&_nc_ht=scontent.fcgy1-3.fna&_nc_gid=yrxIAaR9hcFTlPsyjUFFDQ&oh=00_AfaprlUj--jLEJIaQKF-6LGCuJLy6WIJ9t6UndviXJliuQ&oe=68CBC6E6" alt="Alih Hassan Mocoy">
              </div>
              <h4>Alih Hassan Mocoy</h4>
              <p class="developer-role">Full Stack Developer & Backend Architect</p>
              <p>Developed the robust backend infrastructure including database design, API endpoints, and secure authentication system for multi-role access.</p>
              <div class="developer-contributions">
                <span class="contribution-tag">Database Design</span>
                <span class="contribution-tag">API Development</span>
                <span class="contribution-tag">Security Implementation</span>
              </div>
            </div>
          </div>
          <div class="carousel-item">
            <div class="developer-card">
              <div class="developer-image">
                <img src="https://scontent.fcgy2-2.fna.fbcdn.net/v/t39.30808-1/540388949_1619346332358566_4007910877159205150_n.jpg?stp=dst-jpg_s200x200_tt6&_nc_cat=104&ccb=1-7&_nc_sid=e99d92&_nc_eui2=AeEiDQta3p_Sv6hI0uGH_FXGnwBBoiHJW7qfAEGiIclbukcwmSn5eCxes1odZUMufDfQFGlKHy4rF17UHph95aUG&_nc_ohc=RUjyIh5fefMQ7kNvwFcKf0e&_nc_oc=Adnc-JsbZr_dU4wnUa9-wdrSbg4abz9f2Iv9-8jluwLHm0jGslIwmXzo8RKKs1O1vLo&_nc_zt=24&_nc_ht=scontent.fcgy2-2.fna&_nc_gid=aBhegdVSrStFh4paJ98HCw&oh=00_AfaYOFsdyzRPIDBN99Bqv2eBUNZt7rDTQX8eBluNMLUSrg&oe=68CF024F" alt="Henrique Montehermoso">
              </div>
              <h4>Henrique Montehermoso</h4>
              <p class="developer-role">Full Stack Developer & Backend Architect</p>
              <p>Developed the robust backend infrastructure including database design, API endpoints, and secure authentication system for multi-role access.</p>
              <div class="developer-contributions">
                <span class="contribution-tag">Database Design</span>
                <span class="contribution-tag">API Development</span>
                <span class="contribution-tag">Security Implementation</span>
              </div>
            </div>
          </div>
        </div>
      
      </div>
    </div>
  </section>
  

  <section id="about" class="how-it-works">
    <div class="container">
      <h3 class="section-title">How It Works</h3>
      <div class="steps">
        <div class="step">
          <div class="step-number">1</div>
          <h4>Create Projects</h4>
          <p>Set up projects with detailed descriptions, objectives, and timelines.</p>
        </div>
        <div class="step">
          <div class="step-number">2</div>
          <h4>Assign Tasks</h4>
          <p>Break down projects into manageable tasks and assign them to team members.</p>
        </div>
        <div class="step">
          <div class="step-number">3</div>
          <h4>Track Progress</h4>
          <p>Monitor progress with visual indicators and automated status updates.</p>
        </div>
        <div class="step">
          <div class="step-number">4</div>
          <h4>Meet Deadlines</h4>
          <p>Complete projects on time with smart reminders and deadline tracking.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="cta">
    <div class="container">
      <h3>Ready to Transform Your Workflow?</h3>
      <p>Join thousands of academic professionals who are already benefiting from our task management system.</p>
      <a href="auth/register.php" class="btn primary-btn">Create Your Account Now</a>
    </div>
  </section>

  <footer>
    <div class="container">
      <div class="footer-content">
        <div class="footer-column">
          <h4>Task Management System</h4>
          <p>Optimizing academic workflow through intelligent task management and deadline tracking.</p>
          <div class="social-links">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
          </div>
        </div>
        <div class="footer-column">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="#">Home</a></li>
            <li><a href="#">Features</a></li>
            <li><a href="#">Developers</a></li>
            <li><a href="#">About Us</a></li>
          </ul>
        </div>
        <div class="footer-column">
          <h4>Resources</h4>
          <ul>
            <li><a href="#">Help Center</a></li>
            <li><a href="#">Blog</a></li>
            <li><a href="#">Tutorials</a></li>
            <li><a href="#">FAQs</a></li>
          </ul>
        </div>
        <div class="footer-column">
          <h4>Contact Us</h4>
          <ul>
            <li><i class="fas fa-map-marker-alt"></i> Zamboanga Peninsula Polytechnic State University</li>
            <li><i class="fas fa-phone"></i> +63 123 456 7890</li>
            <li><i class="fas fa-envelope"></i> info@taskmanager.edu</li>
          </ul>
        </div>
      </div>
      <div class="copyright">
        <p>&copy; 2025 Zamboanga Peninsula Polytechnic State University. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <script>
    document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
      document.querySelector('.nav-links').classList.toggle('active');
    });
  </script>
  <script>
    (function() {
      const track = document.querySelector('.carousel-track');
      if (!track) return;
      let items = Array.from(track.querySelectorAll('.carousel-item'));
      const carouselRoot = document.querySelector('.developer-carousel');
      let running = true;
      let lastTime = performance.now();
      let offset = 0;
      const speed = 40;
      let singleLoopWidth = 0;

      function calcLoopWidth() {
        items = Array.from(track.querySelectorAll('.carousel-item'))
        const originalCount = items.length / 2 >= 1 ? items.length / 2 : items.length;
        let width = 0;
        const gap = parseInt(getComputedStyle(track).gap || '0');
        for (let i = 0; i < originalCount; i++) {
          const rect = items[i].getBoundingClientRect();
          width += rect.width;
          if (i < originalCount - 1) width += gap;
        }
        singleLoopWidth = Math.max(width, 1);
      }
      function ensureClones() {
        const currentItems = Array.from(track.querySelectorAll('.carousel-item'));
        if (currentItems.length >= 8) return;
        currentItems.forEach(item => {
          const clone = item.cloneNode(true);
          track.appendChild(clone);
        });
      }

      ensureClones();
      calcLoopWidth();

      function step(now) {
        const dt = (now - lastTime) / 1000; 
        lastTime = now;
        if (running) {
          offset += speed * dt;
          if (offset >= singleLoopWidth) {
            offset = offset - singleLoopWidth;
            // use modulo effect; this keeps transform within reasonable range
          }
          track.style.transform = `translateX(-${offset}px)`;
        }
        requestAnimationFrame(step);
      }
      carouselRoot.addEventListener('mouseenter', () => { running = false; });
      carouselRoot.addEventListener('mouseleave', () => { running = true; lastTime = performance.now(); });
      carouselRoot.addEventListener('focusin', () => { running = false; });
      carouselRoot.addEventListener('focusout', () => { running = true; lastTime = performance.now(); });

      window.addEventListener('resize', () => { calcLoopWidth(); });
      lastTime = performance.now();
      requestAnimationFrame(step);
    })();
  </script>
  <!-- Floating CTA + Back to top -->
  <a href="auth/register.php" class="floating-cta" title="Create an account">
    <i class="fas fa-user-plus" aria-hidden="true"></i>
    <span>Create Account</span>
  </a>
  <button class="back-to-top" aria-label="Back to top"><i class="fas fa-chevron-up"></i></button>

  <script>
    // Smooth scroll for internal links
    document.querySelectorAll('a[href^="#"]').forEach(a=>{
      a.addEventListener('click', function(e){
        const href = this.getAttribute('href');
        if (href.length>1) {
          e.preventDefault();
          const el = document.querySelector(href);
          if (!el) return;
          el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });

    // Navbar shrink on scroll + reveal elements
    (function(){
      const navbar = document.querySelector('.navbar');
      const backBtn = document.querySelector('.back-to-top');
      const floating = document.querySelector('.floating-cta');

      function onScroll(){
        const sc = window.scrollY || window.pageYOffset;
        if (sc > 60) navbar.style.padding = '0.6rem 1rem'; else navbar.style.padding = '';
        if (sc > 400) backBtn.classList.add('visible'); else backBtn.classList.remove('visible');
      }

      // IntersectionObserver reveal
      const io = new IntersectionObserver((entries)=>{
        entries.forEach(entry=>{
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            // stagger children if grid
            if (entry.target.matches('.feature-grid') || entry.target.matches('.developer-grid')){
              const children = Array.from(entry.target.querySelectorAll('.feature-card, .developer-card, .carousel-item'));
              children.forEach((c,i)=> setTimeout(()=> c.classList.add('visible'), i*120));
            }
          }
        });
      },{ rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

      document.querySelectorAll('.feature-grid .feature-card, .developer-card, .how-it-works .step, .testimonial-card, .hero-content').forEach(el=> io.observe(el));

      backBtn.addEventListener('click', ()=> window.scrollTo({ top:0, behavior:'smooth' }));

      window.addEventListener('scroll', onScroll, { passive:true });
      onScroll();
    })();
  </script>
</body>
</html>