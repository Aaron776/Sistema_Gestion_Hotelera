<!-- topbar -->
        <div class="topbar">
            <div class="page-title">
                <h2><i class="fas fa-chart-pie" style="margin-right: 12px; font-size: 1.4rem; color:#3b82f6;"></i> Panel de Control</h2>
            </div>
            <div class="user-info">
                <div class="notification-badge" id="btnNotificaciones" style="position: relative; cursor: pointer;">
                    <i class="far fa-bell fa-lg" style="color:#4b5563;"></i>
                    <span class="badge-dot" id="badgeNotificaciones" style="display: none;"></span>
                    
                    <!-- Dropdown de Notificaciones -->
                    <div id="dropdownNotificaciones" class="notificaciones-dropdown" style="display: none; position: absolute; right: 0; top: 35px; width: 300px; background: white; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-radius: 12px; border: 1px solid #e2e8f0; z-index: 1000; overflow: hidden;">
                        <div style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; font-weight: 600; font-size: 0.9rem; color: #1e293b;">
                            Notificaciones
                        </div>
                        <div id="listaNotificaciones" style="max-height: 300px; overflow-y: auto; padding: 8px 0;">
                            <!-- Las notificaciones se cargarán aquí -->
                            <div style="padding: 12px 16px; font-size: 0.8rem; color: #64748b; text-align: center;">Cargando...</div>
                        </div>
                    </div>
                </div>
                <div class="avatar">
                    <span><?php echo substr($_SESSION['nombre'], 0, 2); ?></span>
                </div>
            </div>
        </div>