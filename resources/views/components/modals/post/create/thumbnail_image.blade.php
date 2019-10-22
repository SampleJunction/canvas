<div class="modal fade" id="modal-image" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog" id="featured-image-unsplash-modal" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <p class="font-weight-bold lead">{{ __('canvas::posts.forms.image.header') }}</p>

                <featured-image-uploader
                    :post="'{{ $data['id']->toString() }}'"
                    :caption="'{{ old('thumbnail_image_caption') }}'"
                    :unsplash="'{{ config('canvas.unsplash.access_key') }}'"
                    :path="'{{ config('canvas.path') }}'">
                </featured-image-uploader>
            </div>
            <div class="modal-footer">
                <button class="btn btn-link text-muted" data-dismiss="modal">{{ __('canvas::buttons.general.done') }}</button>
            </div>
        </div>
    </div>
</div>
<script>
    import ThumbnailImageUploader from "../../../../../js/components/ThumbnailImageUploader";
    export default {
        components: {ThumbnailImageUploader}
    }
</script>
<script>
    import FeaturedImageUploader from "../../../../../js/components/FeaturedImageUploader";
    export default {
        components: {FeaturedImageUploader}
    }
</script>