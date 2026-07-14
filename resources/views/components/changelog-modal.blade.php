<div class="modal fade" id="changelog-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Historique des versions</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @foreach (config('changelog.releases', []) as $release)
                    <div class="mb-4">
                        <h5 class="font-w600">
                            Version {{ $release['version'] }}
                            <span class="font-size-sm text-muted font-w400">— {{ \Carbon\Carbon::parse($release['date'])->translatedFormat('d F Y') }}</span>
                        </h5>
                        @foreach ($release['groups'] as $groupName => $items)
                            <div class="mb-2">
                                <div class="font-w600 font-size-sm text-primary">{{ $groupName }}</div>
                                <ul class="mb-0">
                                    @foreach ($items as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                    @if (!$loop->last)
                        <hr>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
