<table {{ $attributes->merge(['class' => 'table align-items-center mb-0']) }}>
    <thead>
    <tr>
        @foreach($headers as $header)
            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">{{ $header }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            @foreach($row as $key => $value)
                <td>
                    <p class="text-sm font-weight-bold mb-0">{{ $value }}</p>
                </td>
            @endforeach
            <td>
                <div class="d-flex align-items-center">

                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>