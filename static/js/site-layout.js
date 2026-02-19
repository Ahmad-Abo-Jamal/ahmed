(function(){
  // Custom Cursor
  function initCustomCursor() {
    // Create cursor element if it doesn't exist
    if (!document.getElementById('customCursor')) {
      const cursorDiv = document.createElement('div');
      cursorDiv.id = 'customCursor';
      cursorDiv.className = 'custom-cursor';
      document.body.prepend(cursorDiv);
    }

    const customCursor = document.getElementById('customCursor');
    let mouseX = 0;
    let mouseY = 0;

    document.addEventListener('mousemove', (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;
      customCursor.style.left = mouseX + 'px';
      customCursor.style.top = mouseY + 'px';
    });

    // Cursor interaction with clickable elements
    document.addEventListener('mouseenter', (e) => {
      const target = e.target;
      if (isClickable(target)) {
        customCursor.classList.add('active');
      }
    }, true);

    document.addEventListener('mouseleave', (e) => {
      const target = e.target;
      if (isClickable(target)) {
        customCursor.classList.remove('active');
      }
    }, true);

    function isClickable(element) {
      if (!element || element.nodeType !== 1) return false;
      try {
        return typeof element.matches === 'function' && element.matches('a, button, .btn, .carousel-btn, .sector-tab, input[type="submit"], input[type="button"], .read-more, .carousel-dot, input, textarea, select, [onclick], .clickable');
      } catch (e) {
        return false;
      }
    }

    // Hide cursor on mouse leave
    document.addEventListener('mouseenter', () => {
      customCursor.style.opacity = '1';
    });

    document.addEventListener('mouseleave', () => {
      customCursor.style.opacity = '0';
    });
  }

  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCustomCursor);
  } else {
    initCustomCursor();
  }

  const navItems = [
    { href: '/index.html', label: 'الرئيسية' },
    { href: '/about.html', label: 'من أنا' },
    { href: '/articles.html', label: 'المقالات' },
    { href: '/courses.html', label: 'الدورات' },
    { href: '/timeline.html', label: 'الخط الزمني' },
    { href: '/dictionary.html', label: 'القاموس' },
    { href: '/influencers.html', label: 'المؤثرون' }
  ];
  const current = location.pathname.replace(/\/$/, '') || '/index.html';
  const links = navItems.map(item => `<a class="${current === item.href ? 'active' : ''}" href="${item.href}">${item.label}</a>`).join('');

  const headerTarget = document.getElementById('site-header');
  if (headerTarget) {
    headerTarget.innerHTML = `<header class="site-shell-header"><div class="inner"><div class="site-top"><a class="site-brand" href="/index.html"><i class="fas fa-sparkles"></i><span>أحمد أبو المجد</span></a><nav class="site-links">${links}</nav></div></div></header>`;
  }

  const footerTarget = document.getElementById('site-footer');
  if (footerTarget) {
    footerTarget.innerHTML = `<footer class="site-shell-footer"><div class="inner"><p>واجهة موحدة بتصميم إبداعي ✨</p><div><a href="/index.html">الرئيسية</a> • <a href="/articles.html">المحتوى</a> • <a href="/courses.html">الأكاديمية</a></div></div></footer>`;
  }
  document.body.classList.add('with-shared-layout');
})();
