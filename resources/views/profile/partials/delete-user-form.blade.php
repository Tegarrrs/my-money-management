<div class="text-muted mb-4" style="font-size:13px;">
    Setelah akun Anda dihapus, semua data dan riwayat transaksi keuangan Anda akan terhapus secara permanen. Pastikan Anda telah mengunduh informasi apa pun yang ingin dipertahankan sebelum melanjutkan.
</div>

<button type="button" class="btn-dp btn-dp-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
    <i class="bi bi-trash3"></i> Hapus Akun
</button>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:450px;">
        <div class="modal-content" style="border-radius:16px;border:1px solid #e5e7eb;">
            <div class="modal-header" style="border-bottom:1px solid #f3f4f6;padding:18px 24px;">
                <h5 class="modal-title" id="deleteAccountModalLabel" style="font-size:15px;font-weight:700;">Konfirmasi Hapus Akun</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')
                
                <div class="modal-body" style="padding:20px 24px;">
                    @if(auth()->user()->password)
                    <p class="mb-3" style="font-size:13px;color:#4b5563;">
                        Apakah Anda yakin ingin menghapus akun ini? Masukkan password Anda untuk mengonfirmasi bahwa Anda ingin menghapus secara permanen.
                    </p>
                    
                    <div class="mb-2">
                        <label for="password" class="form-label" style="font-size:13px;font-weight:600;">Password</label>
                        <input id="password" name="password" type="password" class="form-control" style="font-size:13px;border-radius:9px;" placeholder="Masukkan password" required />
                        @error('password', 'userDeletion')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
                    </div>
                    @else
                    <p class="mb-3" style="font-size:13px;color:#4b5563;">
                        Apakah Anda yakin ingin menghapus akun ini? Akun yang dihapus tidak dapat dipulihkan kembali.
                    </p>
                    @endif
                </div>
                
                <div class="modal-footer" style="border-top:1px solid #f3f4f6;padding:16px 24px;">
                    <button type="button" class="btn-dp btn-dp-default" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-dp btn-dp-danger"><i class="bi bi-exclamation-triangle"></i> Hapus Permanen</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->userDeletion->isNotEmpty())
    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
                deleteModal.show();
            });
        </script>
    @endpush
@endif
