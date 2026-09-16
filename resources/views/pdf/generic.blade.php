<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ ucfirst($model) }} Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 10px;
        }

        h1 {
            text-align: center;
            font-size: 18px;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
            font-size: 10px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background-color: #f9f9f9;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h1>{{ ucfirst($model) }} Report</h1>
    <table>
        <thead>
            <tr>
                <th>Sr. No.</th>
                @foreach ($columns as $column)
                    <th>{{ ucfirst(str_replace('_', ' ', $column)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    @foreach ($columns as $column)
                        <td>{{ $item->$column }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
