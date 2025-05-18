{{-- Partial template for displaying ecotox results in a table --}}
<div class="table-responsive">
    <table class="table table-hover table-striped table-sm">
        <thead>
            <tr>
                <th>Ecotox ID</th>
                <th>Substance</th>
                <th>Matrix</th>
                <th>Type</th>
                <th>Taxonomic Group</th>
                <th>Scientific Name</th>
                <th>Endpoint</th>
                <th>Effect</th>
                <th>Duration</th>
                <th>Concentration</th>
                <th>Reliability</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($results as $result)
                <tr>
                    <td>{{ $result->ecotox_id }}</td>
                    <td>
                        <div class="d-flex flex-column">
                            <span>{{ $result->substance->name ?? $result->substance_name }}</span>
                            <small class="text-muted">{{ $result->cas_number }}</small>
                        </div>
                    </td>
                    <td>
                        @if($result->matrix_habitat == 'freshwater')
                            <span class="badge bg-info">Freshwater</span>
                        @elseif($result->matrix_habitat == 'marine water')
                            <span class="badge bg-primary">Marine</span>
                        @else
                            {{ $result->matrix_habitat }}
                        @endif
                    </td>
                    <td>
                        @if($result->acute_or_chronic == 'acute')
                            <span class="badge bg-warning">Acute</span>
                        @elseif($result->acute_or_chronic == 'chronic')
                            <span class="badge bg-success">Chronic</span>
                        @else
                            {{ $result->acute_or_chronic }}
                        @endif
                    </td>
                    <td>{{ $result->taxonomic_group }}</td>
                    <td>
                        <em>{{ $result->scientific_name }}</em>
                        @if($result->common_name)
                            <br><small class="text-muted">{{ $result->common_name }}</small>
                        @endif
                    </td>
                    <td>{{ $result->endpoint }}</td>
                    <td>{{ $result->effect_measurement }}</td>
                    <td>
                        @if($result->duration)
                            {{ $result->duration }}
                            @if(isset($result->total_test_duration) && !empty($result->total_test_duration))
                                <br><small class="text-muted">Total: {{ $result->total_test_duration }}</small>
                            @endif
                        @endif
                    </td>
                    <td>
                        @if($result->concentration_value)
                            <span data-bs-toggle="tooltip" title="{{ $result->estimate_variability ?? 'No variability data' }}">
                                {{ $result->concentration_qualifier }} {{ number_format($result->concentration_value, 4) }} 
                                @if($result->unit_concentration)
                                    {{ $result->unit_concentration }}
                                @endif
                            </span>
                        @endif
                    </td>
                    <td>
                        @php
                            $reliabilityClass = 'secondary';
                            if ($result->credFinalEvaluations && $result->credFinalEvaluations->isNotEmpty()) {
                                $evaluation = $result->credFinalEvaluations->first();
                                if ($evaluation->cred_final_score >= 80) {
                                    $reliabilityClass = 'success';
                                } elseif ($evaluation->cred_final_score >= 60) {
                                    $reliabilityClass = 'info';
                                } elseif ($evaluation->cred_final_score >= 40) {
                                    $reliabilityClass = 'warning';
                                } else {
                                    $reliabilityClass = 'danger';
                                }
                            }
                        @endphp
                        <span class="badge bg-{{ $reliabilityClass }}">
                            {{ $result->reliability_study ?? 'Not evaluated' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="btn-group">
                            {{-- <a href="{{ route('ecotox.search.show', $result->id) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if(Auth::check() && Auth::user()->can('edit ecotox'))
                            <a href="{{ route('ecotox.search.edit', $result->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit Record">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif --}}
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center">No results found in this category.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>