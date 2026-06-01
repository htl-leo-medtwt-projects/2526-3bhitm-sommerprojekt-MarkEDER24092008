# TODO - Profile avatar upload

## Step 1: User creation avatar upload
- [x] Update `public/create_user_form.html` to include file input for avatar and set `enctype="multipart/form-data"`.


- [x] Update `public/sql/createUserMySql.php` to save uploaded avatar and store relative path in `user.avatar_url`.


## Step 2: Profile page shows avatar from DB
- [x] Update profile page to show the user avatar from `user.avatar_url` (fallback to 👤).

## Step 3: Click-to-change avatar
- [x] Make avatar on `profile.html` clickable and trigger change flow (confirm + file picker).
- [x] Add new endpoint `public/sql/updateAvatarMySql.php` to update `user.avatar_url`.


## Step 4: Navbar profile link shows avatar
- [ ] Locate navbar/header markup for “profile” `<a>` and render avatar there.


## Step 5: Test
- [x] Create user with avatar, verify DB + profile display.
- [x] Change avatar from profile page, verify navbar + profile refresh.

