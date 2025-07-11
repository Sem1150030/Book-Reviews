<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    public function reviews(){
        return $this->hasMany(Review::class);
    }

    public function scopeTitle(Builder $query, string $title): Builder{
            return $query->where('title', 'LIKE',  '%' . $title . '%');
    }

    public function scopePopular(Builder $query, $from = null, $to = null): Builder{
        return $query->withReviewsCount()->orderBy('reviews_count', 'desc');
    }

    public function scopeWithReviewsCount(Builder $query, $from = null, $to = null): Builder{
        return $query->withCount([
            'reviews' => fn (Builder $q) => $this->dateRangeFilter($q, $from, $to)
        ]);
    }

    public function scopeWithAvgRating(Builder $query, $from = null, $to = null): Builder{
        return $query->withAvg([
            'reviews' => fn (Builder $q) => $this->dateRangeFilter($q, $from, $to)
            ], 'rating');
    }



    public function scopeHighestRated(Builder $query, $from = null, $to = null): Builder {
    return $query->withAvgRating($from, $to)->orderBy('reviews_avg_rating', 'desc');
}

    public function scopeMinReviews(Builder $query, int $minReviews): Builder{
        return $query->having('reviews_count', '>=', $minReviews);
    }

    private function dateRangeFilter(Builder $q , $from = null, $to = null){
        if ($from && !$to){
                $q->where('created_at', '>=', $from);
            }
            elseif(!$from && $to){
                $q->where('created_at', '<=', $to);
            }
             elseif($q->whereBetween('created_at', [$from, $to]));

    }

    public function scopePopularLastMonth(Builder $query): Builder  {
        return $query->popular(now()->subMonth(), now())
        ->highestRated(now()->subMonth(), now())->minReviews(2);
    }
    public function scopePopularLast6Months(Builder $query): Builder  {
        return $query
        ->popular(now()->subMonths(6), now())
        ->highestRated(now()->subMonths(6), now())
        ->minReviews(5);
    }

    public function scopeHighestRatedLastMonth(Builder $query): Builder {
    $from = now()->subMonth();
    $to = now();

    return $query
        ->highestRated($from, $to)
        ->popular($from, $to)
        ->minReviews(2);
}

    public function scopeHighestRatedLast6Months(Builder $query): Builder  {
        return $query
        ->highestRated(now()->subMonths(6), now())
        ->popular(now()->subMonths(6), now())
        ->minReviews(5);
    }

     protected static function booted(){
        static::updated(fn(Book $book) => cache()->forget('book:' . $book->id));
        static::deleted(fn(Book $book) => cache()->forget('book:' . $book->id));
    }
}
