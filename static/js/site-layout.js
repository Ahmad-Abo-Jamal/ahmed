(function(){
  const navItems = [
    { href: '/index.html', label: 'الرئيسية' },
    { href: '/articles.html', label: 'المقالات' },
    { href: '/courses.html', label: 'الدورات' },
    { href: '/dictionary.html', label: 'القاموس' },
    { href: '/timeline.html', label: 'الخط الزمني' },
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
