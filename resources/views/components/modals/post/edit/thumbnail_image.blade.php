<div class="modal fade" id="modal-thumbnail_image" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog" id="thumbnail_image-unsplash-modal" role="document">
        <div class="modal-content">
            <div class="modal-body">
                {{--@php dd($post->thumbnail_image); @endphp--}}
                @if($post->thumbnail_image)
                <div id="current-image">
                    <img src="{{asset($post->thumbnail_image)}}" class="w-100">
                    <div class="input-group py-2">
                        <input type="text" name="thumbnail_image_caption" title="Thumbnail Image Caption"  @if($post->thumbnail_image_caption) placeholder="{{$post->thumbnail_image_caption}}" @else placeholder="Type caption for image (optional)" @endif class="form-control border-0 px-0">
                    </div>
                </div>
                @endif
                <p class="font-weight-bold lead">{{ __('Thumbnail') }}</p>

                {{--<thumbnail-image-uploader
                    :post="'{{ $data['post']->id }}'"
                    :url="'{{ $data['post']->thumbnail_image }}'"
                    :caption="'{{ old('thumbnail_image_caption') }}'"
                    :unsplash="'{{ config('canvas.unsplash.access_key') }}'"
                    :path="'{{ config('canvas.path') }}'">
                </thumbnail-image-uploader>--}}
                Please choose any image:  <input type="file" class="form-control" name="thumbnail_image"><br><br>
            </div>
            <div class="modal-footer">
                <button class="btn btn-link text-muted" data-dismiss="modal">{{ __('canvas::buttons.general.done') }}</button>
            </div>
        </div>
    </div>
</div>
