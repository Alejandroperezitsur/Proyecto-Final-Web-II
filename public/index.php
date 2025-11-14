<?php
header('Location: app.php?r=/login');
exit;
?>
                                <input type="password" class="form-control" id="password" 
                                       name="password" required>
                                <div class="invalid-feedback">
                                    Por favor ingrese su contraseña.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Iniciar Sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
    // Validación ligera y UX: prevenir envíos vacíos, trimming y feedback
    (function() {
        'use strict';
        const form = document.getElementById('login-form');
        if (!form) return;
        form.addEventListener('submit', (event) => {
            const idEl = document.getElementById('identifier');
            const passEl = document.getElementById('password');
            if (idEl) idEl.value = (idEl.value || '').trim();
            if (passEl) passEl.value = (passEl.value || '').trim();
            const valid = form.checkValidity();
            if (!valid) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    })();
    </script>
</body>
</html>
