<div id="container-list-recycle-bin" class="bg-white rounded-lg shadow-sm overflow-hidden">
    @if ($documents->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-hover">

                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-hover">
                        Dokumen
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-hover">
                        Jenis & Tahun
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-hover">
                        Indikator
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-hover">
                        Diupload
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-hover">

                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($documents as $document)
                <tr class="hover:bg-gray-50 document-table" id="row-{{ $document->uuid }}" data-document-uuid="{{ $document->uuid }}">
                    <td class="px-6 py-4">
                        <input id="check-{{ $document->uuid }}" type="checkbox" data-document-uuid="{{ $document->uuid }}" class="w-4 h-4 border border-default-medium rounded-xs bg-neutral-secondary-medium focus:ring-2 focus:ring-brand-soft" />
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <img class="h-10 w-8 rounded object-cover mr-3"
                                src="{{ Storage::disk('documents')->url($document->cover_path) }}?v={{ $document->updated_at->timestamp }}"
                                alt="Cover">
                            <div>
                                <div class="text-sm font-medium text-gray-900 text-hover">
                                    {{ Str::limit($document->title, 50) }}
                                </div>
                                <div class="text-sm text-gray-500 text-hover">
                                    {{ $document->creator->name ?? 'Unknown' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ $document->type === 'publication' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                            {{ $document->type === 'publication' ? 'Publikasi' : 'BRS' }}
                        </span>
                        <div class="text-sm text-gray-500 mt-1 text-hover">{{ $document->year }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 text-hover">
                        {{ $document->indicator->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 text-hover">
                        {{ $document->created_at->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex items-center justify-start space-x-2">
                            <button type="button"
                                id="restore-single-{{ $document->uuid }}"
                                class="text-green-600 hover:text-green-700 p-1 hover-sound"
                                title="Restore">
                                <i class="fas fa-redo" aria-hidden="true"></i>
                            </button>
                            <button type="button"
                                id="forcedelete-single-{{ $document->uuid }}"
                                class="text-red-600 hover:text-red-700 p-1 hover-sound"
                                title="Hapus dari Server">
                                <i class="fas fa-trash" aria-hidden="true"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($documents->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $documents->withQueryString()->links() }}
    </div>
    @endif
    @else
    <div class="px-6 py-12 text-center text-gray-500">
        <i class="fas fa-folder-open text-4xl mb-4" aria-hidden="true"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2 text-hover">Belum ada dokumen</h3>
        <p class="text-hover">Mulai dengan mengupload dokumen pertama Anda.</p>
        <a href="{{ route('admin.documents.create') }}"
            class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors hover-sound">
            <i class="fas fa-plus mr-2" aria-hidden="true"></i>
            <span class="text-hover">Upload Dokumen</span>
        </a>
    </div>
    @endif
</div>