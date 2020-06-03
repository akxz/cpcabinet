@extends('layouts.home')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col">{{ __('Answers') }}</div>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (Session::has('success'))
                        <div class="alert alert-success">
                            {{Session::get('success')}}
                        </div>
                    @endif

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <select class="form-control" id="project">
                                <option value="0">Select Project</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <input
                              type="text"
                              id="dates"
                              placeholder="Select dates"
                              class="dates form-control" />
                          </div>
                          <div class="form-group col-md-2">
                            <select class="form-control" id="limit">
                                <option value="10">Limit 10</option>
                                <option value="20">Limit 20</option>
                                <option value="50">Limit 50</option>
                                <option value="100">Limit 100</option>
                            </select>
                            <input type="hidden" id="offset" value="0" />
                        </div>
                        <div class="form-group col-md-2">
                            <button id="search_btn" class="btn btn-primary btn">
                                {{ __('Search') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="resp" class="row justify-content-center"></div>

    <div class="row justify-content-center">
        <div class="col-md-12 mt-2">
            <div class="card">
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (Session::has('success'))
                        <div class="alert alert-success">
                            {{Session::get('success')}}
                        </div>
                    @endif

                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th scope="col" style="width: 50%;">{{__('Phrase')}}</th>
                          <th scope="col">{{__('Sound')}}</th>
                          <th scope="col">{{__('Value')}}</th>
                        </tr>
                      </thead>
                      <tbody id="phrases">
                      </tbody>
                    </table>

                    <div class="row justify-content-center">
                        <button type="button" class="btn btn-primary btn-more" id="more" style="display: none;">
                            Show more
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="{{ asset('js/datepicker.min.js') }}" defer></script>
<script src="{{ asset('js/i18n/datepicker.en.js') }}" defer></script>


<script>
function showMoreBtn() {
    $('#more').css('display', 'block');
}

function hideMoreBtn() {
    $('#more').css('display', 'none');
}

function resetOffset() {
    $('#offset').val('0');
}

function incrementOffset(v) {
    let offset = $('#offset').val();
    offset = Number.parseInt(offset, 10) + Number.parseInt(v, 10);
    $('#offset').val(offset);
}

// Получение списка проектов
function setProjects() {
    $.get('/api/projects', {}, function(resp) {
        let dataArray = JSON.parse(resp);
        let html = '<option value="0">Select Project</option>';
        dataArray['data'].forEach(item => {
            html += '<option value="' + item.id + '">' + item.name + '</option>';
        });

        $('#project').html(html);
    });
}

// Генерация ссылки на звуковой файл
function makeSoundLink(item) {
    let d = item.datetime
        .split(' ')[0]
        .replace(/-/g, '');
    let url = 'http://176.57.215.154/zlata/'+
        item.script + '/' + d + '/' + item.call_id + '_' + item.phone + '.mp3';

    return '<a href="' + url + '" target="_blank"><img src="../img/volume-21.svg" /></a>';
}

// Получение списка фраз
function getAnswers() {
    let project = $('#project').val();
    let dates = $('#dates').val();
    let limit = $('#limit').val();
    let offset = $('#offset').val();
    if (project == '0') {
        alert('Select Project');
        return false;
    } else if (dates == '') {
        alert('Select Dates');
        return false;
    }

    if (offset == '0') {
        // Очищаем таблицу и вставляем новые строки
        $('#phrases').html('Searching...');
    }

    $.post(
        '/api/answers',
        {
            'project' : project,
            'dates' : dates,
            'limit' : limit,
            'offset' : offset
        },
        function(resp) {
            let dataArray = JSON.parse(resp);
            let html = '';
            let i = 0;

            if (dataArray['data'].length > 0) {
                dataArray['data'].forEach(item => {
                    i++;
                    html += '<tr data-project="'+ project +'">'+
                    '<td class="phrase">' + item.text + '</td>'+
                    '<td>' + makeSoundLink(item) + '</td>'+
                    '<td><button type="button"'+
                            'data-answer="yes"'+
                            'class="btn btn-primary btn-sm btn-answer">Yes'+
                        '</button>'+
                        '<button type="button"'+
                            'data-answer="no"'+
                            'class="btn btn-danger btn-sm btn-answer ml-3">No'+
                        '</button></td></tr>';
                });
            } else {
                html = 'No results';
            }

            // Проверим, надо ли очищать результаты
            if (offset == '0') {
                // Очищаем таблицу и вставляем новые строки
                $('#phrases').html(html);
            } else {
                // Добавляем строки в конец таблицы
                $('#phrases').append(html);
            }

            // Проверяем кол-во полученных результатов и меняем offset
            if (i == limit) {
                incrementOffset(limit);
                showMoreBtn();
            } else {
                resetOffset();
                hideMoreBtn();
            }
    });
}

// Удаляет все строки таблицы, содержащие заданную фразу
function removePhrases(phrase) {
    let th;
    $('.phrase').each(function() {
        th = $(this);
        if (th.html() == phrase) {
            th.parent('tr').remove();
        }
    });
}

// Сохранение тональности для фразы
function saveActionValue(el) {
    let tr = el.parent('td').parent('tr');
    let phrase = tr.find('.phrase').html();

    $.post(
        '/api/answers/new',
        {
            'project' : tr.attr('data-project'),
            'answer' : el.attr('data-answer'),
            'phrase' : phrase
        },
        function(resp) {
            let dataArray = JSON.parse(resp);

            if (dataArray['success'].length > 0) {
                // Удаляем все строки с этой фразой
                removePhrases(phrase);
            } else {
                tr.addClass('table-danger');
            }
    });
}

$(document).ready(function() {
    setProjects();

    $('.dates').datepicker({
        language: 'en',
        timepicker: true,
        range: true,
        toggleSelected: false,
        multipleDatesSeparator: ' - ',
        dateFormat: "yyyy-mm-dd",
        timeFormat: "hh:ii",
    });

    // Скрываем кнопку "Показать еще" при смене проекта
    $(document).on('change', '#project', function() {
        hideMoreBtn();
    });

    // Поиск
    $(document).on('click', '#search_btn', function(e) {
        e.preventDefault();
        resetOffset();
        getAnswers();
    });

    // Кнопка "Показать еще"
    $(document).on('click', '.btn-more', function(e) {
        e.preventDefault();
        getAnswers();
    });

    // Оценка тональности фразы
    $(document).on('click', '.btn-answer', function(e) {
        e.preventDefault();
        saveActionValue($(this));
    });
});
</script>
@endpush
