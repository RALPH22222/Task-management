let ALL_USERS = [];
let MANAGERS = [];
let PRIORITIES = [];
let STATUSES = [];
let PROJECTS = [];

function setServerData(allUsers, managers, priorities, statuses, projects) {
    ALL_USERS = allUsers;
    MANAGERS = managers;
    PRIORITIES = priorities;
    STATUSES = statuses;
    PROJECTS = projects;
}

function buildAddProjectHTML() {
    // Only show employees for member selection (exclude admins)
    const teamUsers = ALL_USERS.filter(u => u.role === 'employee');
    const managersList = MANAGERS; // Only managers for manager selection
    const employeesList = teamUsers; // Only employees for member selection
    const managerCheckboxes = managersList.map(u => 
        '<div class="form-check">' +
          '<input class="form-check-input" type="checkbox" value="' + u.user_id + '" id="member_' + u.user_id + '" name="members[]">' +
          '<label class="form-check-label" for="member_' + u.user_id + '">' + u.full_name + ' <span class="text-muted small">(' + u.role + ')</span></label>' +
        '</div>'
    ).join('');
    const employeeCheckboxes = employeesList.map(u => 
        '<div class="form-check">' +
          '<input class="form-check-input" type="checkbox" value="' + u.user_id + '" id="member_' + u.user_id + '" name="members[]">' +
          '<label class="form-check-label" for="member_' + u.user_id + '">' + u.full_name + ' <span class="text-muted small">(' + u.role + ')</span></label>' +
        '</div>'
    ).join('');

    const managerOptions = MANAGERS.length ? MANAGERS.map(m => '<option value="' + m.user_id + '">' + m.full_name + '</option>').join('') : '<option value="">No managers available</option>';

    const priorityOptions = PRIORITIES.map(p => '<option value="' + p.priority_id + '">' + p.name + '</option>').join('');
    const statusOptions = STATUSES.map(s => '<option value="' + s.status_id + '">' + s.name + '</option>').join('');
    const managersCount = managersList.length;
    const employeesCount = employeesList.length;

    return `<form id="swalAddProjectForm" class="ap-modal" enctype="multipart/form-data">
        <div class="mb-3 ap-section">
          <h6 class="mb-2 ap-heading">Project Details</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Project Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="project_name" id="swal_project_name" maxlength="150" required>
              <div class="form-text">Give your project a clear, concise name.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="3" placeholder="Describe the project (optional)"></textarea>
              <div class="form-text">Add context to help your team understand the scope.</div>
            </div>
          </div>
        </div>

        <div class="mb-3 ap-section">
          <h6 class="mb-2 ap-heading">Team Members</h6>
          <ul class="nav nav-tabs" id="swalMembersTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tab-managers" data-bs-toggle="tab" data-bs-target="#tabpane-managers" type="button" role="tab">Managers <span class="badge bg-secondary ms-1">${managersCount}</span></button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-employees" data-bs-toggle="tab" data-bs-target="#tabpane-employees" type="button" role="tab">Employees <span class="badge bg-secondary ms-1">${employeesCount}</span></button>
            </li>
          </ul>
          <div class="tab-content border border-top-0 rounded-bottom tab-content-scrollable">
            <div class="tab-pane fade show active" id="tabpane-managers" role="tabpanel">
              <div class="d-flex align-items-center justify-content-between gap-2 p-2 border-bottom sticky-top bg-white ap-sticky-sub">
                <input type="text" class="form-control form-control-sm filter-input" placeholder="Search managers..." id="filterManagers">
                <div class="form-check m-0">
                  <input class="form-check-input" type="checkbox" id="selectAllManagers">
                  <label class="form-check-label small" for="selectAllManagers">Select all</label>
                </div>
              </div>
              <div id="listManagers" class="p-2 ap-checklist">
                ${managerCheckboxes || '<div class="text-muted">No managers found.</div>'}
              </div>
            </div>
            <div class="tab-pane fade" id="tabpane-employees" role="tabpanel">
              <div class="d-flex align-items-center justify-content-between gap-2 p-2 border-bottom sticky-top bg-white ap-sticky-sub">
                <input type="text" class="form-control form-control-sm filter-input" placeholder="Search employees..." id="filterEmployees">
                <div class="form-check m-0">
                  <input class="form-check-input" type="checkbox" id="selectAllEmployees">
                  <label class="form-check-label small" for="selectAllEmployees">Select all</label>
                </div>
              </div>
              <div id="listEmployees" class="p-2 ap-checklist">
                ${employeeCheckboxes || '<div class="text-muted">No employees found.</div>'}
              </div>
            </div>
          </div>

          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" value="1" id="swal_add_me_manager" name="add_me_manager" checked>
            <label class="form-check-label" for="swal_add_me_manager">Add me as project manager</label>
          </div>
          <div class="mt-2" id="swal_manager_select" style="display:none">
            <label class="form-label">Select a manager</label>
            <select class="form-select" name="manager_user_id">
              <option value="">-- choose manager --</option>
              ${managerOptions}
            </select>
            <div class="form-text">If you uncheck the option above, choose a manager here.</div>
          </div>
        </div>

        <div class="accordion ap-section" id="swalTaskAccordion">
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingTask">
              <button class="accordion-button collapsed ap-accordion-btn" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTask">
                Create an initial task
              </button>
            </h2>
            <div id="collapseTask" class="accordion-collapse collapse" data-bs-parent="#swalTaskAccordion">
              <div class="accordion-body">
                <input type="hidden" name="create_initial_task" id="swal_create_initial_task_hidden" value="">
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label">Task Title</label>
                    <input type="text" class="form-control" name="task_title" id="swal_task_title" placeholder="Initial task title">
                    <div class="form-text">Defaults to your project name when opened.</div>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Priority</label>
                    <select class="form-select" name="priority_id">${priorityOptions}</select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status_id">${statusOptions}</select>
                  </div>
                </div>
                <div class="mt-2">
                  <label class="form-label">Task Description</label>
                  <textarea class="form-control" name="task_description" rows="3" placeholder="Describe what needs to be done..."></textarea>
                  <div class="form-text">Provide detailed information about the task.</div>
                </div>
                <div class="row g-2 mt-1">
                  <div class="col-md-6">
                    <label class="form-label">Deadline</label>
                    <input type="date" class="form-control" name="deadline">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Reminder Date & Time</label>
                    <input type="datetime-local" class="form-control" name="reminder_datetime" placeholder="Set reminder">
                    <div class="form-text">Optional reminder before deadline.</div>
                  </div>
                </div>
                <div class="row g-2 mt-1">
                  <div class="col-md-6">
                    <label class="form-label">Attachment (optional)</label>
                    <input type="file" class="form-control" name="attachment" accept="*/*">
                  </div>
                </div>
                <div class="mt-2">
                  <label class="form-label">Comment (optional)</label>
                  <textarea class="form-control" name="task_comment" rows="2" placeholder="Write an initial comment..."></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    `;
}

function buildEditProjectHTML(p) {
    // Only show employees for member selection (exclude admins)
    const teamUsers = ALL_USERS.filter(u => u.role === 'employee');
    const managersList = MANAGERS; // Only managers for manager selection
    const employeesList = teamUsers; // Only employees for member selection
    const managersCount = managersList.length;
    const employeesCount = employeesList.length;
    const managerCheckboxes = managersList.map(u => `
      <div class="form-check">
        <input class="form-check-input" type="checkbox" value="${u.user_id}" id="edit_member_${p.project_id}_${u.user_id}" name="members[]" ${p.members.includes(parseInt(u.user_id)) ? 'checked' : ''}>
        <label class="form-check-label" for="edit_member_${p.project_id}_${u.user_id}">${u.full_name} <span class="text-muted small">(${u.role})</span></label>
      </div>
    `).join('');
    const employeeCheckboxes = employeesList.map(u => `
      <div class="form-check">
        <input class="form-check-input" type="checkbox" value="${u.user_id}" id="edit_member_${p.project_id}_${u.user_id}" name="members[]" ${p.members.includes(parseInt(u.user_id)) ? 'checked' : ''}>
        <label class="form-check-label" for="edit_member_${p.project_id}_${u.user_id}">${u.full_name} <span class="text-muted small">(${u.role})</span></label>
      </div>
    `).join('');
    const managerOptions = MANAGERS.length ? MANAGERS.map(m => `<option value="${m.user_id}">${m.full_name}</option>`).join('') : '<option value="">No managers available</option>';
    const priorityOptions = PRIORITIES.map(p => `<option value="${p.priority_id}">${p.name}</option>`).join('');
    const statusOptions = STATUSES.map(s => `<option value="${s.status_id}">${s.name}</option>`).join('');

    return `
      <form id="swalEditProjectForm" class="ap-modal" enctype="multipart/form-data">
        <input type="hidden" name="project_id" value="${p.project_id}">
        <div class="mb-3 ap-section">
          <h6 class="mb-2 ap-heading">Project Details</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Project Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="project_name" maxlength="150" value="${(p.project_name||'').replace(/"/g,'&quot;').replace(/'/g,'&#39;')}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="3">${(p.description||'').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;')}</textarea>
            </div>
          </div>
        </div>
        <div class="mb-3 ap-section">
          <h6 class="mb-2 ap-heading">Team Members</h6>
          <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#edit-tabpane-managers" type="button" role="tab">Managers <span class="badge bg-secondary ms-1">${managersCount}</span></button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#edit-tabpane-employees" type="button" role="tab">Employees <span class="badge bg-secondary ms-1">${employeesCount}</span></button></li>
          </ul>
          <div class="tab-content border border-top-0 rounded-bottom tab-content-scrollable">
            <div class="tab-pane fade show active" id="edit-tabpane-managers" role="tabpanel">
              <div class="d-flex align-items-center justify-content-between gap-2 p-2 border-bottom sticky-top bg-white ap-sticky-sub">
                <input type="text" class="form-control form-control-sm filter-input" placeholder="Search managers..." id="edit-filterManagers">
                <div class="form-check m-0">
                  <input class="form-check-input" type="checkbox" id="edit-selectAllManagers">
                  <label class="form-check-label small" for="edit-selectAllManagers">Select all</label>
                </div>
              </div>
              <div id="edit-listManagers" class="p-2 ap-checklist">
                ${managerCheckboxes || '<div class="text-muted">No managers found.</div>'}
              </div>
            </div>
            <div class="tab-pane fade" id="edit-tabpane-employees" role="tabpanel">
              <div class="d-flex align-items-center justify-content-between gap-2 p-2 border-bottom sticky-top bg-white ap-sticky-sub">
                <input type="text" class="form-control form-control-sm filter-input" placeholder="Search employees..." id="edit-filterEmployees">
                <div class="form-check m-0">
                  <input class="form-check-input" type="checkbox" id="edit-selectAllEmployees">
                  <label class="form-check-label small" for="edit-selectAllEmployees">Select all</label>
                </div>
              </div>
              <div id="edit-listEmployees" class="p-2 ap-checklist">
                ${employeeCheckboxes || '<div class="text-muted">No employees found.</div>'}
              </div>
            </div>
          </div>
          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" value="1" id="swal_edit_add_me_manager" name="add_me_manager" ${p.is_creator_manager ? 'checked' : ''}>
            <label class="form-check-label" for="swal_edit_add_me_manager">Set me as project manager</label>
          </div>
          <div class="mt-2" id="swal_edit_manager_select" style="display:${p.is_creator_manager ? 'none' : 'block'}">
            <label class="form-label">Select a manager</label>
            <select class="form-select" name="manager_user_id">
              <option value="">-- choose manager --</option>
              ${managerOptions}
            </select>
          </div>
        </div>
        <div class="accordion ap-section" id="editTaskAccordion">
          <div class="accordion-item">
            <h2 class="accordion-header" id="edit-headingTask">
              <button class="accordion-button collapsed ap-accordion-btn" type="button" data-bs-toggle="collapse" data-bs-target="#edit-collapseTask">
                Create an initial task
              </button>
            </h2>
            <div id="edit-collapseTask" class="accordion-collapse collapse" data-bs-parent="#editTaskAccordion">
              <div class="accordion-body">
                <input type="hidden" name="create_initial_task" id="swal_edit_create_initial_task_hidden" value="">
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label">Task Title</label>
                    <input type="text" class="form-control" name="task_title" id="swal_edit_task_title" placeholder="Initial task title">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Priority</label>
                    <select class="form-select" name="priority_id">${priorityOptions}</select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status_id">${statusOptions}</select>
                  </div>
                </div>
                <div class="mt-2">
                  <label class="form-label">Task Description</label>
                  <textarea class="form-control" name="task_description" rows="3" placeholder="Describe what needs to be done..."></textarea>
                  <div class="form-text">Provide detailed information about the task.</div>
                </div>
                <div class="row g-2 mt-1">
                  <div class="col-md-6">
                    <label class="form-label">Deadline</label>
                    <input type="date" class="form-control" name="deadline">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Reminder Date & Time</label>
                    <input type="datetime-local" class="form-control" name="reminder_datetime" placeholder="Set reminder">
                    <div class="form-text">Optional reminder before deadline.</div>
                  </div>
                </div>
                <div class="row g-2 mt-1">
                  <div class="col-md-6">
                    <label class="form-label">Attachment (optional)</label>
                    <input type="file" class="form-control" name="attachment" accept="*/*">
                  </div>
                </div>
                <div class="mt-2">
                  <label class="form-label">Comment (optional)</label>
                  <textarea class="form-control" name="task_comment" rows="2" placeholder="Write an initial comment..."></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    `;
}

function initializeEventHandlers() {
    const btnAddProject = document.getElementById('btnAddProject');
    if (btnAddProject) {
        btnAddProject.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Add Project',
                html: buildAddProjectHTML(),
                width: 800,
                showCancelButton: true,
                confirmButtonText: 'Create Project',
                focusConfirm: false,
                didOpen: () => {
                    const addMeChk = document.getElementById('swal_add_me_manager');
                    const mgrWrap = document.getElementById('swal_manager_select');
                    const projectNameInput = document.getElementById('swal_project_name');
                    const taskTitleInput = document.getElementById('swal_task_title');
                    const collapseTask = document.getElementById('collapseTask');
                    const createTaskHidden = document.getElementById('swal_create_initial_task_hidden');

                    addMeChk.addEventListener('change', () => {
                        mgrWrap.style.display = addMeChk.checked ? 'none' : 'block';
                    });

                    collapseTask.addEventListener('shown.bs.collapse', () => {
                        createTaskHidden.value = '1';
                        if (taskTitleInput && projectNameInput && !taskTitleInput.value) {
                            taskTitleInput.value = projectNameInput.value || '';
                        }
                    });
                    collapseTask.addEventListener('hidden.bs.collapse', () => {
                        createTaskHidden.value = '';
                    });

                    if (projectNameInput && taskTitleInput) {
                        projectNameInput.addEventListener('input', () => {
                            if (createTaskHidden.value === '1') {
                                taskTitleInput.value = projectNameInput.value;
                            }
                        });
                    }

                    const filterList = (containerId, inputId) => {
                        const input = document.getElementById(inputId);
                        const container = document.getElementById(containerId);
                        if (!input || !container) return;
                        input.addEventListener('input', () => {
                            const q = input.value.toLowerCase();
                            container.querySelectorAll('.form-check').forEach(item => {
                                const label = item.querySelector('label');
                                const text = (label ? label.textContent : '').toLowerCase();
                                item.style.display = text.includes(q) ? '' : 'none';
                            });
                        });
                    };
                    filterList('listManagers','filterManagers');
                    filterList('listEmployees','filterEmployees');

                    const wireSelectAll = (checkboxId, containerId) => {
                        const master = document.getElementById(checkboxId);
                        const container = document.getElementById(containerId);
                        if (!master || !container) return;
                        master.addEventListener('change', () => {
                            const checks = container.querySelectorAll('input[type="checkbox"][name="members[]"]');
                            checks.forEach(c => { c.checked = master.checked; });
                        });
                    };
                    wireSelectAll('selectAllManagers','listManagers');
                    wireSelectAll('selectAllEmployees','listEmployees');
                },
                preConfirm: () => {
                    const form = document.getElementById('swalAddProjectForm');
                    const fd = new FormData(form);
                    if (!fd.get('project_name')) {
                        Swal.showValidationMessage('Project name is required');
                        return false;
                    }
                    if (!fd.get('add_me_manager') && !fd.get('manager_user_id')) {
                        Swal.showValidationMessage('Please choose a manager or check "Add me as project manager".');
                        return false;
                    }
                    if (fd.get('create_initial_task')) {
                        if (!fd.get('task_title') || !fd.get('deadline')) {
                            Swal.showValidationMessage('Task title and deadline are required for the initial task.');
                            return false;
                        }
                    }

                    const postForm = document.getElementById('addProjectForm');
                    postForm.innerHTML = '<input type="hidden" name="add_project" value="1">';
                    for (const [k, v] of fd.entries()) {
                        if (v instanceof File) continue;
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = k;
                        input.value = v;
                        postForm.appendChild(input);
                    }
                    const fileInput = form.querySelector('input[type=file][name=attachment]');
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        const realFile = document.createElement('input');
                        realFile.type = 'file';
                        realFile.name = 'attachment';
                        postForm.appendChild(realFile);
                        const dt = new DataTransfer();
                        dt.items.add(fileInput.files[0]);
                        realFile.files = dt.files;
                    }
                    postForm.submit();
                    return false;
                }
            });
        });
    }

    document.querySelectorAll('.btnEditProject').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const pid = parseInt(btn.dataset.pid);
            const p = PROJECTS.find(x => parseInt(x.project_id) === pid);
            if (!p) return;
            Swal.fire({
                title: 'Edit Project',
                html: buildEditProjectHTML(p),
                width: 800,
                showCancelButton: true,
                confirmButtonText: 'Save Changes',
                didOpen: () => {
                    const addMeChk = document.getElementById('swal_edit_add_me_manager');
                    const mgrWrap = document.getElementById('swal_edit_manager_select');
                    if (addMeChk && mgrWrap) {
                        addMeChk.addEventListener('change', () => { mgrWrap.style.display = addMeChk.checked ? 'none' : 'block'; });
                    }
                    const collapseTask = document.getElementById('edit-collapseTask');
                    const createTaskHidden = document.getElementById('swal_edit_create_initial_task_hidden');
                    const taskTitleInput = document.getElementById('swal_edit_task_title');
                    const projNameInput = document.querySelector('#swalEditProjectForm input[name="project_name"]');
                    if (collapseTask && createTaskHidden) {
                        collapseTask.addEventListener('shown.bs.collapse', () => {
                            createTaskHidden.value = '1';
                            if (taskTitleInput && projNameInput && !taskTitleInput.value) {
                                taskTitleInput.value = projNameInput.value || '';
                            }
                        });
                        collapseTask.addEventListener('hidden.bs.collapse', () => { createTaskHidden.value = ''; });
                    }
                    if (projNameInput && taskTitleInput && createTaskHidden) {
                        projNameInput.addEventListener('input', () => {
                            if (createTaskHidden.value === '1') {
                                taskTitleInput.value = projNameInput.value;
                            }
                        });
                    }

                    const filterList = (containerId, inputId) => {
                        const input = document.getElementById(inputId);
                        const container = document.getElementById(containerId);
                        if (!input || !container) return;
                        input.addEventListener('input', () => {
                            const q = input.value.toLowerCase();
                            container.querySelectorAll('.form-check').forEach(item => {
                                const label = item.querySelector('label');
                                const text = (label ? label.textContent : '').toLowerCase();
                                item.style.display = text.includes(q) ? '' : 'none';
                            });
                        });
                    };
                    filterList('edit-listManagers','edit-filterManagers');
                    filterList('edit-listEmployees','edit-filterEmployees');

                    const wireSelectAll = (checkboxId, containerId) => {
                        const master = document.getElementById(checkboxId);
                        const container = document.getElementById(containerId);
                        if (!master || !container) return;
                        master.addEventListener('change', () => {
                            const checks = container.querySelectorAll('input[type="checkbox"][name="members[]"]');
                            checks.forEach(c => { c.checked = master.checked; });
                        });
                    };
                    wireSelectAll('edit-selectAllManagers','edit-listManagers');
                    wireSelectAll('edit-selectAllEmployees','edit-listEmployees');
                },
                preConfirm: () => {
                    const form = document.getElementById('swalEditProjectForm');
                    const fd = new FormData(form);
                    if (!fd.get('project_name')) {
                        Swal.showValidationMessage('Project name is required');
                        return false;
                    }
                    if (fd.get('create_initial_task') === '1') {
                        if (!fd.get('task_title') || !fd.get('deadline')) {
                            Swal.showValidationMessage('Task title and deadline are required for the initial task.');
                            return false;
                        }
                    }
                    const postForm = document.getElementById('editProjectForm');
                    postForm.innerHTML = '<input type="hidden" name="edit_project" value="1">';
                    for (const [k,v] of fd.entries()) {
                        if (v instanceof File) continue;
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = k;
                        input.value = v;
                        postForm.appendChild(input);
                    }
                    const fileInput = form.querySelector('input[type=file][name=attachment]');
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        const realFile = document.createElement('input');
                        realFile.type = 'file';
                        realFile.name = 'attachment';
                        postForm.appendChild(realFile);
                        const dt = new DataTransfer();
                        dt.items.add(fileInput.files[0]);
                        realFile.files = dt.files;
                    }
                    postForm.submit();
                    return false;
                }
            });
        });
    });

    document.querySelectorAll('.btnDeleteProject').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const form = btn.closest('form.deleteProjectForm');
            if (form) {
                Swal.fire({
                    title: 'Delete project?',
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
            }
        });
    });
}

function showFlashMessages(successMsg, errorMsg) {
    if (successMsg) Swal.fire({icon:'success', title:'Success', text: successMsg});
    if (errorMsg) Swal.fire({icon:'error', title:'Error', text: errorMsg});
}

function enableTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });
}

function setupSearchAutoSubmit() {
    const headerSearchForm = Array.from(document.querySelectorAll('form')).find(f => f.querySelector('input[name="q"]') && f.closest('.container-fluid'));
    if (headerSearchForm) {
        const searchInput = headerSearchForm.querySelector('input[name="q"]');
        if (searchInput) {
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
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initializeEventHandlers();
    enableTooltips();
    setupSearchAutoSubmit();
});
