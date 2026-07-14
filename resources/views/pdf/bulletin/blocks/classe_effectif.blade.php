<table style="border-collapse:collapse; font-size:10px;">
    <tr>
        <th style="border:1px solid #000; padding:2px 8px;text-align:center">Classe</th>
        <th style="border:1px solid #000; padding:2px 8px;text-align:center">Effectif</th>
    </tr>
    <tr>
        <td style="border:1px solid #000; padding:2px 8px; text-align:center;">{{ $classe->nom }}</td>
        <td style="border:1px solid #000; padding:2px 8px; text-align:center;">{{ count($classe->eleves) }}</td>
    </tr>
</table>
