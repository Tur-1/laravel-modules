<?php

namespace Tur1\laravelmodules\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseModel extends Model
{

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    protected $search = [];
    public static function filters()
    {
        return [];
    }

    protected static function booted()
    {
        foreach (static::filters() as $filterClass) {
            static::addGlobalScope(new $filterClass);
        }
        static::addGlobalScope('search', function (Builder $builder) {
            $builder->search();
        });
    }
    protected function scopeSearch($query, $searchValue = null)
    {

        $searchValue = $searchValue ?? request('search');

        if ($searchValue) {
            $query->where(function ($query) use ($searchValue) {
                foreach ($this->search as $field) {
                    if ($this->isRelationship($field)) {
                        [$relation, $relationField] = explode('.', $field);
                        $query->orWhereHas($relation, function ($query) use ($relationField, $searchValue) {
                            $query->where($relationField, 'like', '%' . $searchValue . '%');
                        });
                    } else {
                        $query->orWhere($field, 'like', '%' . $searchValue . '%');
                    }
                }
            });
        }

        return $query;
    }
    private function isRelationship($field)
    {
        return str_contains($field, '.');
    }
}
