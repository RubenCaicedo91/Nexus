<!-- Modal para previsualizar archivos sin descargarlos -->
<div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Previsualizar documento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-0" style="min-height:60vh;">
        <div class="p-3 border-bottom">
          <div class="row">
            <div class="col-md-8">
              <strong id="filePreviewStudentName">Estudiante:</strong>
              <div id="filePreviewStudentExtra" class="small text-muted"></div>
            </div>
            <div class="col-md-4 text-end">
              <div id="filePreviewContactEmail" class="small"></div>
              <div id="filePreviewContactPhone" class="small"></div>
            </div>
          </div>
        </div>
        <iframe id="filePreviewFrame" src="" frameborder="0" style="width:100%; height:65vh;"></iframe>
      </div>
      <div class="modal-footer">
        <a id="filePreviewOpenNew" class="btn btn-outline-primary" href="#" target="_blank">Abrir en pestaña nueva</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.body.addEventListener('click', function(e){
    var el = e.target.closest('.preview-file');
    if (!el) return;
    e.preventDefault();
    var url = el.getAttribute('data-href') || el.getAttribute('href');
    if (!url) return;

    var iframe = document.getElementById('filePreviewFrame');
    var openLink = document.getElementById('filePreviewOpenNew');
    iframe.src = url;
    openLink.href = url;

    // Rellenar datos de contacto si vienen en atributos data-
    var name = el.getAttribute('data-name') || '';
    var email = el.getAttribute('data-email') || '';
    var phone = el.getAttribute('data-phone') || '';
    var studentExtra = document.getElementById('filePreviewStudentExtra');
    var emailEl = document.getElementById('filePreviewContactEmail');
    var phoneEl = document.getElementById('filePreviewContactPhone');
    var studentNameEl = document.getElementById('filePreviewStudentName');

    if (studentNameEl) studentNameEl.textContent = name ? ('Estudiante: ' + name) : 'Estudiante:';
    if (studentExtra) studentExtra.textContent = '';
    if (emailEl) emailEl.textContent = email ? ('Email: ' + email) : '';
    if (phoneEl) phoneEl.textContent = phone ? ('Tel: ' + phone) : '';

    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      var modalEl = document.getElementById('filePreviewModal');
      var modal = new bootstrap.Modal(modalEl);
      modal.show();
    } else {
      // Fallback: abrir en nueva pestaña
      window.open(url, '_blank');
    }
  });
});
</script>
@endpush
<!-- Partial removed: file preview via iframe replaced by direct downloads. -->
