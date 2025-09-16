const ROLE_OPTIONS = ['admin','manager','employee'];

function buildAddUserHTML() {
  const roleOptions = ROLE_OPTIONS.map(r => `<option value="${r}">${r}</option>`).join('');
  return `
    <form id="swalAddUserForm" class="ap-modal">
      <div class="mb-3 ap-section">
        <h6 class="mb-2 ap-heading">User Details</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="full_name" placeholder="e.g. Juan Dela Cruz" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" placeholder="name@example.com" required>
          </div>
        </div>
      </div>
      <div class="mb-1 ap-section">
        <h6 class="mb-2 ap-heading">Security & Role</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Role</label>
            <select class="form-select" name="role">${roleOptions}</select>
            <div class="form-text">Choose the permission level for this user.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="password" minlength="6" placeholder="Minimum 6 characters" required>
            <div class="form-text">The user can change this later.</div>
          </div>
        </div>
      </div>
    </form>
  `;
}

function buildEditUserHTML(u) {
  const roleOptions = ROLE_OPTIONS.map(r => `<option value="${r}" ${u.role===r?'selected':''}>${r}</option>`).join('');
  return `
    <form id="swalEditUserForm" class="ap-modal">
      <input type="hidden" name="user_id" value="${u.user_id}">
      <div class="mb-3 ap-section">
        <h6 class="mb-2 ap-heading">User Details</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="full_name" value="${(u.full_name||'').replace(/\"/g,'&quot;')}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" value="${(u.email||'').replace(/\"/g,'&quot;')}" required>
          </div>
        </div>
      </div>
      <div class="mb-1 ap-section">
        <h6 class="mb-2 ap-heading">Security & Role</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Role</label>
            <select class="form-select" name="role">${roleOptions}</select>
            <div class="form-text">Update the user's permission level.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="password" minlength="6" placeholder="Leave blank to keep current">
          </div>
        </div>
      </div>
    </form>
  `;
}
function initializeManageUsers() {
  (function(){
    const headerSearchForm = Array.from(document.querySelectorAll('form')).find(f => f.querySelector('input[name="q"]') && f.closest('.container-fluid'));
    if (!headerSearchForm) return;
    const searchInput = headerSearchForm.querySelector('input[name="q"]');
    if (!searchInput) return;
    let timer = null;
    const submitWithPageReset = () => {
      let pageHidden = headerSearchForm.querySelector('input[name="page"]');
      if (!pageHidden) { 
        pageHidden = document.createElement('input'); 
        pageHidden.type = 'hidden'; 
        pageHidden.name = 'page'; 
        headerSearchForm.appendChild(pageHidden); 
      }
      pageHidden.value = '1';
      headerSearchForm.submit();
    };
    searchInput.addEventListener('input', () => { 
      if (timer) clearTimeout(timer); 
      timer = setTimeout(submitWithPageReset, 400); 
    });
  })();

  const btnAddUser = document.getElementById('btnAddUser');
  if (btnAddUser) {
    btnAddUser.addEventListener('click', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Add User',
        html: buildAddUserHTML(),
        width: 600,
        showCancelButton: true,
        confirmButtonText: 'Create User',
        preConfirm: () => {
          const form = document.getElementById('swalAddUserForm');
          const fd = new FormData(form);
          if (!fd.get('full_name') || !fd.get('email') || !fd.get('password')) {
            Swal.showValidationMessage('Please fill all required fields.');
            return false;
          }
          const email = fd.get('email');
          if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            Swal.showValidationMessage('Please enter a valid email.');
            return false;
          }
          const postForm = document.getElementById('addUserForm');
          postForm.innerHTML = '<input type="hidden" name="add_user" value="1">';
          for (const [k,v] of fd.entries()) {
            const input = document.createElement('input'); 
            input.type = 'hidden'; 
            input.name = k; 
            input.value = v; 
            postForm.appendChild(input);
          }
          postForm.submit();
          return false;
        }
      });
    });
  }

  document.querySelectorAll('.btnEditUser').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const u = {
        user_id: parseInt(btn.dataset.uid),
        full_name: btn.dataset.fullname || '',
        email: btn.dataset.email || '',
        role: btn.dataset.role || 'employee',
      };
      Swal.fire({
        title: 'Edit User',
        html: buildEditUserHTML(u),
        width: 600,
        showCancelButton: true,
        confirmButtonText: 'Save Changes',
        preConfirm: () => {
          const form = document.getElementById('swalEditUserForm');
          const fd = new FormData(form);
          if (!fd.get('full_name') || !fd.get('email')) {
            Swal.showValidationMessage('Full name and email are required.');
            return false;
          }
          const email = fd.get('email');
          if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            Swal.showValidationMessage('Please enter a valid email.');
            return false;
          }
          const postForm = document.getElementById('editUserForm');
          postForm.innerHTML = '<input type="hidden" name="edit_user" value="1">';
          for (const [k,v] of fd.entries()) { 
            const input = document.createElement('input'); 
            input.type = 'hidden'; 
            input.name = k; 
            input.value = v; 
            postForm.appendChild(input); 
          }
          postForm.submit();
          return false;
        }
      });
    });
  });
  document.querySelectorAll('.btnDeleteUser').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const form = btn.closest('form.deleteUserForm');
      Swal.fire({
        title: 'Delete user?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
      }).then(result => { 
        if (result.isConfirmed) { 
          form.submit(); 
        } 
      });
    });
  });
}
function handleFlashMessages(successMsg, errorMsg) {
  if (successMsg) Swal.fire({icon:'success', title:'Success', text: successMsg});
  if (errorMsg) Swal.fire({icon:'error', title:'Error', text: errorMsg});
}

document.addEventListener('DOMContentLoaded', function() {
  initializeManageUsers();
});
