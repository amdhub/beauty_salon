@props(['value'=>true])
<label class="flex items-center gap-3 rounded-xl bg-stone-50 p-4"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked(old('active',$value))><span>Actif <span class="block text-xs font-normal text-muted">Disponible pour l’activité du salon</span></span></label>
