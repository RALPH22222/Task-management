document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    const statusLabels = window.statusLabels || [];
    const statusData = window.statusData || [];
    const statusColors = ['#8B2C2C', '#D4AF37', '#EBCFB2', '#A67C52', '#4A4A4A'];
    
    const ctx = document.getElementById('statusChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: statusColors,
                    borderColor: '#fff',
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                family: '"Inter", sans-serif',
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            family: '"Inter", sans-serif',
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            family: '"Inter", sans-serif',
                            size: 12
                        },
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    const statsCards = document.querySelectorAll('.stats-card');
    statsCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.5s ease-out';
    });

    let delay = 0;
    statsCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, delay);
        delay += 100;
    });
    const tableRows = document.querySelectorAll('.table-hover tbody tr');
    tableRows.forEach(row => {
        row.style.transition = 'background-color 0.2s ease';
        row.addEventListener('mouseenter', () => {
            row.style.backgroundColor = 'rgba(139, 44, 44, 0.03)';
        });
        row.addEventListener('mouseleave', () => {
            row.style.backgroundColor = '';
        });
    });
    const chartContainers = document.querySelectorAll('.chart-container');
    chartContainers.forEach(container => {
        container.style.position = 'relative';
        const loader = document.createElement('div');
        loader.className = 'chart-loader';
        loader.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        container.appendChild(loader);
        setTimeout(() => {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
            }, 500);
        }, 1000);
    });
    function updateTaskStatus(taskId, statusId) {
        fetch('update_task_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                task_id: taskId,
                status_id: statusId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating task status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating task status');
        });
    }
    document.querySelectorAll('.status-update-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const taskId = this.dataset.taskId;
            const statusId = this.dataset.statusId;
            updateTaskStatus(taskId, statusId);
        });
    });
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in-up');
    });

    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const menu = this.nextElementSibling;
            menu.classList.toggle('show');
        });
    });
    document.addEventListener('click', function(e) {
        if (!e.target.matches('.dropdown-toggle')) {
            const dropdowns = document.querySelectorAll('.dropdown-menu');
            dropdowns.forEach(dropdown => {
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            });
        }
    });

    // Project Details Modal Functionality
    const projectDetailsModal = new bootstrap.Modal(document.getElementById('projectDetailsModal'));
    
    // Function to populate modal with project data
    function populateProjectModal(projectCard) {
        const projectId = projectCard.dataset.projectId;
        const projectName = projectCard.dataset.projectName;
        const projectDescription = projectCard.dataset.projectDescription;
        const createdBy = projectCard.dataset.createdBy;
        const createdAt = projectCard.dataset.createdAt;
        const totalTasks = projectCard.dataset.totalTasks;
        const myTasks = projectCard.dataset.myTasks;
        const myOverdue = projectCard.dataset.myOverdue;
        const myNextDeadline = projectCard.dataset.myNextDeadline;

        // Update modal title
        document.getElementById('modalProjectName').textContent = projectName;
        
        // Update project overview
        document.getElementById('modalProjectDescription').textContent = 
            projectDescription || 'No description available';
        document.getElementById('modalCreatedBy').textContent = createdBy || '-';
        document.getElementById('modalCreatedAt').textContent = 
            createdAt ? new Date(createdAt).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }) : '-';
        
        // Update task statistics
        document.getElementById('modalTotalTasks').textContent = totalTasks || '0';
        document.getElementById('modalMyTasks').textContent = myTasks || '0';
        document.getElementById('modalOverdueTasks').textContent = myOverdue || '0';
        
        // Update next deadline
        const nextDeadlineElement = document.getElementById('modalNextDeadline');
        if (myNextDeadline && myNextDeadline !== 'null') {
            const deadlineDate = new Date(myNextDeadline);
            const today = new Date();
            const isOverdue = deadlineDate < today;
            
            nextDeadlineElement.innerHTML = `
                <span class="${isOverdue ? 'text-danger' : 'text-warning'}">
                    <i class="fas fa-calendar-alt me-1"></i>
                    ${deadlineDate.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    })}
                    ${isOverdue ? '<small class="ms-2">(Overdue)</small>' : ''}
                </span>
            `;
        } else {
            nextDeadlineElement.textContent = 'No upcoming deadlines';
        }
        
        // Update View Tasks button
        const viewTasksBtn = document.getElementById('viewTasksBtn');
        viewTasksBtn.onclick = function() {
            // You can implement navigation to tasks page here
            // For now, we'll just close the modal
            projectDetailsModal.hide();
            // Example: window.location.href = `tasks.php?project_id=${projectId}`;
        };
    }

    // Add click event listeners to project cards
    document.querySelectorAll('.clickable-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't trigger modal if clicking on the view details button
            if (e.target.closest('.view-details-btn')) {
                return;
            }
            
            populateProjectModal(this);
            projectDetailsModal.show();
        });
    });

    // Add click event listeners to view details buttons
    document.querySelectorAll('.view-details-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent card click event
            const projectCard = this.closest('.clickable-card');
            populateProjectModal(projectCard);
            projectDetailsModal.show();
        });
    });

    // Add keyboard support for accessibility
    document.querySelectorAll('.clickable-card').forEach(card => {
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'button');
        card.setAttribute('aria-label', 'View project details');
        
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                populateProjectModal(this);
                projectDetailsModal.show();
            }
        });
    });
});
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
    }
`;
document.head.appendChild(style);
