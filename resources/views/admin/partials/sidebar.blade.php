<aside class="admin-sidebar">
  <div class="admin-brand">
    <div class="admin-seal">★</div>
    <div>
      <div class="admin-title">Daet Listens</div>
      <div class="admin-subtitle">Admin Panel</div>
    </div>
  </div>
  <ul class="sidebar-nav">
    <li><a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><span>⊞</span> Overview</a></li>
    <li><a href="{{ route('admin.complaints.index') }}" class="{{ request()->routeIs('admin.complaints.*') ? 'active' : '' }}"><span>📝</span> All Complaints</a></li>
    <li><a href="{{ route('admin.staff.index') }}" class="{{ request()->routeIs('admin.staff.*') ? 'active' : '' }}"><span>👤</span> Staff Accounts</a></li>
    <li style="margin-top: auto;"><a href="{{ route('logout') }}"><span>→</span> Logout</a></li>
  </ul>
</aside>
<style>
  .admin-sidebar { width: 280px; background: rgba(255,255,255,0.03); border-right: 1px solid rgba(201,168,76,0.20); padding: 24px 0; position: relative; z-index: 10; flex-shrink: 0; }
  .admin-brand { padding: 0 20px 24px; border-bottom: 1px solid rgba(201,168,76,0.20); margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
  .admin-seal { width: 40px; height: 40px; background: linear-gradient(135deg, #C9A84C, #E2C06A); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0B1F3A; font-weight: bold; }
  .admin-title { font-family: 'Cormorant Garamond', serif; font-size: 18px; font-weight: 700; color: #fff; }
  .admin-subtitle { font-size: 10px; color: #C9A84C; letter-spacing: 0.1em; text-transform: uppercase; }
  .sidebar-nav { list-style: none; padding: 0; margin: 0; }
  .sidebar-nav li { margin: 2px 0; }
  .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: rgba(255,255,255,0.6); text-decoration: none; font-size: 13px; font-weight: 500; transition: all 0.2s; position: relative; }
  .sidebar-nav a:hover { color: #fff; background: rgba(255,255,255,0.03); }
  .sidebar-nav a.active { color: #C9A84C; background: rgba(201,168,76,0.08); }
  .sidebar-nav a.active::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: #C9A84C; }
  @media (max-width: 768px) { .admin-sidebar { display: none; } }
</style>
