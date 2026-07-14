<table style="border-collapse:collapse; font-size:10px;">
    <tr>
        <th style="border:1px solid #000; padding:2px 8px;">N</th>
        <th style="border:1px solid #000; padding:2px 8px;">D</th>
    </tr>
    <tr>
        <td style="border:1px solid #000; padding:2px 8px; text-align:center; font-weight:bold;">{{ !$eleve->redoublant ? 'X' : '' }}</td>
        <td style="border:1px solid #000; padding:2px 8px; text-align:center; font-weight:bold;">{{ $eleve->redoublant ? 'X' : '' }}</td>
    </tr>
</table>
