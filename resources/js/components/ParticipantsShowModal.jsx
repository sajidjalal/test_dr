import React, { useState, useEffect } from "react";
import axios from "axios";

const ParticipantsShowModal = ({ rootUrl, onClose, selectedChat }) => {
  const [users, setUsers] = useState([]);
  const [selectedUsers, setSelectedUsers] = useState([]);
  const [groupName, setGroupName] = useState("");
  useEffect(() => {
    const fetchUsers = async () => {
      try {
        const res = await axios.get(`${rootUrl}/chats/${selectedChat}/get-chat-participant`);
        setUsers(res.data);
      } catch (err) {
        console.error(err);
      }
    };
    fetchUsers();
  }, [rootUrl]);

    const toggleUser = (userId) => {
        if (selectedUsers.includes(userId)) {
            setSelectedUsers(selectedUsers.filter((id) => id !== userId));
        } else {
            setSelectedUsers([...selectedUsers, userId]);
        }
    };


  const deleteParticipant = async () => {
    if (!selectedUsers.length) return;

    try {
      await axios.post(`${rootUrl}/chats/${selectedChat}/remove-chat-participant`, {participant_user_id: selectedUsers});
      onClose();
    } catch (err) {
      console.error(err);
      alert("Error deleting user");
    }
  };

  return (
    <div className="modal show fade" style={{ display: "block" }} tabIndex="-1">
      <div className="modal-dialog modal-dialog-centered">
        <div className="modal-content">
          {/* Modal Header */}
          <div className="modal-header">
            <h5 className="modal-title">Chat Participants</h5>
            <button type="button" className="btn-close" aria-label="Close" onClick={onClose}></button>
          </div>

          {/* Modal Body */}
          <div className="modal-body">
            {/* Users List */}
            <div className="user-list" style={{ maxHeight: "200px", overflowY: "auto" }}>
              {
                users.map((user) => (
                  <div key={user.id} className="form-check">
                    <input
                      type="checkbox"
                      className="form-check-input"
                      checked={selectedUsers.includes(user.id)}
                      onChange={() => toggleUser(user.id)}
                    />
                    <label className="form-check-label">{user.name}</label>
                  </div>
                ))
              }
              {users.length === 0 && <p className="text-muted">No users available</p>}
            </div>
          </div>

          {/* Modal Footer */}
          <div className="modal-footer">
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              Cancel
            </button>
            <button
              type="button"
              className="btn btn-primary"
              onClick={deleteParticipant}
              disabled={selectedUsers.length === 0}
            >
              Remove Participants
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ParticipantsShowModal;
