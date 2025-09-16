(function(){
  const sidebar = document.querySelector('.sidebar');
  const toggle = document.querySelector('.sidebar-toggle');
  if(!sidebar || !toggle) return;

  const collapsed = localStorage.getItem('tm_sidebar_collapsed') === '1';
  if(collapsed) sidebar.classList.add('collapsed');

  toggle.addEventListener('click', ()=>{
    const isCollapsed = sidebar.classList.toggle('collapsed');
    localStorage.setItem('tm_sidebar_collapsed', isCollapsed ? '1' : '0');
    toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
  });
})();
