<?php

namespace App\Services;

use DB;
use Illuminate\Http\Request;

class AnswerService
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Выводит необработанные фразы
     *
     * @return string json
     */
    public function get()
    {
        // Условия для выборки
        $request = $this->request;

        // Названия скриптов для выбранного проекта
        $scripts = $this->getScripts($request['project']);

        // Проверяем даты
        $dates = $this->getDates();

        // Размеченные ответы для данного проекта
        $checked_answers = $this->getCheckedAnswers($request['project']);

        $query = DB::connection('cpdb')
            ->table('profil')
            ->select('datetime', 'phone', 'text', 'script', 'call_id')
            // ->distinct()
            ->whereIn('script', $scripts)
            ->where('call_status', '=', 'say');

        // Выборка по дате
        if ($dates) {
            if ($dates['from']) {
                $query->where('datetime', '>=', $dates['from']);
            }

            if ($dates['to']) {
                $query->where('datetime', '<=', $dates['to']);
            }
        }

        $query->where('text', '!=', '');

        // Берем только не обработанные фразы
        if (! empty($checked_answers)) {
            $query->whereNotIn('text', $checked_answers);
        }

        $answers = $query->orderBy('id', 'asc')
            ->limit($request['limit'])
            ->offset($request['offset'])
            ->get()
            ->toArray();

        return json_encode(['data' => $answers]);
    }

    /**
     * Сохранение (разметка) ответа
     */
    public function set()
    {
        $request = $this->request;

        $insert = [
            'project_id' => $request['project'],
            'text' => trim($request['phrase']),
            'answer' => trim($request['answer']),
            'language' => $this->getPojectLanguage($request['project']),
        ];

        DB::connection('cpdb')
            ->table('zlata_training')
            ->insert($insert);

        return json_encode(['success' => 'OK']);
    }

    /**
     * Размеченные ответы
     *
     * @param  int $project_id
     * @return array  Массив размеченных фраз для проекта
     */
    private function getCheckedAnswers($project_id)
    {
        $answers = DB::connection('cpdb')
            ->table('zlata_training')
            ->select('text')
            ->where('project_id', $project_id)
            ->get()
            ->pluck('text')
            ->toArray();

        return $answers;
    }

    /**
     * Скрипты проекта
     *
     * @param  int $project_id
     * @return array Массив названий скриптов
     */
    private function getScripts($project_id)
    {
        $scripts = DB::connection('cpdb')
            ->table('callcenter_projects_scripts')
            ->select('script_name')
            ->where('project_id', $project_id)
            ->get()
            ->pluck('script_name')
            ->toArray();

        return $scripts;
    }

    /**
     * Обработка дат для выборки
     *
     * @return array|bool Массив дат
     */
    private function getDates()
    {
        $request = $this->request;

        if (! isset($request['dates'])) {
            return false;
        }

        if (strpos($request['dates'], ' - ') > 0) {
            // Date range
            list($from, $to) = explode(' - ', $request['dates']);
            $dates['from'] = \DateTime::createFromFormat('Y-m-d H:i', $from)
                ->format('Y-m-d H:i:s');
            $dates['to'] = \DateTime::createFromFormat('Y-m-d H:i', $to)
                ->format('Y-m-d H:i:s');
        } else {
            // Single date
            $dates['from'] = \DateTime::createFromFormat('Y-m-d H:i', $request['dates'])
                ->format('Y-m-d H:i:s');
            $dates['to'] = false;
        }

        return $dates;
    }

    /**
     * Язык проекта
     *
     * @param  int $project_id
     * @return string
     */
    private function getPojectLanguage($project_id)
    {
        $return = DB::connection('cpdb')
            ->table('callcenter_projects')
            ->select('language')
            ->where('id', $project_id)
            ->first()
            ->language;

        return $return;
    }
}
